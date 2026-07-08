<?php

namespace Modules\AuthManagement\Service;

use App\Service\BaseService;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Modules\Gateways\Traits\SmsGateway;
use Modules\UserManagement\Repository\OtpVerificationRepositoryInterface;
use Modules\UserManagement\Repository\UserRepositoryInterface;

class AuthService extends BaseService implements Interface\AuthServiceInterface
{
    use SmsGateway;

    protected $userRepository;
    protected $otpVerificationRepository;

    public function __construct(UserRepositoryInterface $userRepository, OtpVerificationRepositoryInterface $otpVerificationRepository)
    {
        parent::__construct($userRepository);
        $this->userRepository = $userRepository;
        $this->otpVerificationRepository = $otpVerificationRepository;
    }

    public function checkClientRoute($request)
    {
        $route = str_contains($request->route()?->getPrefix(), 'customer');
        if ($route) {
            $user = $this->userRepository->findOneBy(criteria: ['phone' => $request->phone_or_email, 'user_type' => CUSTOMER]);
        } else {
            $user = $this->userRepository->findOneBy(criteria: ['phone' => $request->phone_or_email, 'user_type' => DRIVER]);
        }
        return $user;
    }

    private function generateOtp($user, $otp)
    {
        $expires_at = env('APP_MODE') == 'live' ? 3 : 1000;
        $attributes = [
            'phone_or_email' => $user->phone,
            'otp' => $otp,
            'expires_at' => Carbon::now()->addMinutes($expires_at),
        ];
        $verification = $this->otpVerificationRepository->findOneBy(['phone_or_email' => $user->phone]);
        if ($verification) {
            $verification->delete();
        }
        $this->otpVerificationRepository->create(data: $attributes);
        return $otp;
    }

    public function updateLoginUser(string|int $id, array $data): ?Model
    {
        return $this->userRepository->update(id: $id, data: $data);
    }


    public function sendOtpToClient($user)
    {
        $otp = env('APP_MODE') == 'live' ? rand(1000, 9999) : '0000';
        if (self::send($user->phone, $otp) == "not_found") {
            return $this->generateOtp($user, '0000');
        }
        return $this->generateOtp($user, $otp);
    }

    // Verifies a Firebase Phone Auth ID token and returns the decoded claims.
    public function verifyFirebasePhoneToken(string $idToken): ?array
    {
        $firebaseConfig = $this->getFirebasePhoneAuthConfig();
        if (array_key_exists('status', $firebaseConfig) && (int)$firebaseConfig['status'] !== 1) {
            return null;
        }

        $projectId = $firebaseConfig['project_id'] ?? config('services.firebase.project_id');
        if (!$projectId) {
            return null;
        }

        $tokenParts = explode('.', $idToken);
        if (count($tokenParts) !== 3) {
            return null;
        }

        [$headerSegment, $payloadSegment, $signatureSegment] = $tokenParts;
        $header = json_decode($this->base64UrlDecode($headerSegment), true);
        $payload = json_decode($this->base64UrlDecode($payloadSegment), true);

        if (!is_array($header) || !is_array($payload) || ($header['alg'] ?? null) !== 'RS256' || empty($header['kid'])) {
            return null;
        }

        $certs = $this->getFirebaseAuthCerts();
        if (empty($certs[$header['kid']])) {
            return null;
        }

        $signature = $this->base64UrlDecode($signatureSegment);
        $verified = openssl_verify($headerSegment . '.' . $payloadSegment, $signature, $certs[$header['kid']], OPENSSL_ALGO_SHA256);
        if ($verified !== 1) {
            return null;
        }

        return $this->firebasePhoneClaimsAreValid($payload, $projectId) ? $payload : null;
    }

    // Checks whether the Firebase verified phone number belongs to the stored user phone.
    public function phoneMatchesFirebaseToken(string $firebasePhone, string $storedPhone): bool
    {
        $firebaseDigits = preg_replace('/\D+/', '', $firebasePhone);
        $storedDigits = preg_replace('/\D+/', '', $storedPhone);

        if (!$firebaseDigits || !$storedDigits) {
            return false;
        }

        if ($firebaseDigits === $storedDigits) {
            return true;
        }

        if (strlen($firebaseDigits) < 8 || strlen($storedDigits) < 8) {
            return false;
        }

        return str_ends_with($firebaseDigits, $storedDigits) || str_ends_with($storedDigits, $firebaseDigits);
    }

    // Loads Firebase public certificates used to verify Auth ID token signatures.
    private function getFirebaseAuthCerts(): array
    {
        $cacheKey = 'firebase_phone_auth_certs';
        $cachedCerts = Cache::get($cacheKey);
        if (is_array($cachedCerts)) {
            return $cachedCerts;
        }

        try {
            $firebaseConfig = $this->getFirebasePhoneAuthConfig();
            $response = Http::timeout(5)->get($firebaseConfig['auth_cert_url'] ?? config('services.firebase.auth_cert_url'));
            if (!$response->successful()) {
                return [];
            }

            $certs = $response->json();
            if (!is_array($certs)) {
                return [];
            }

            Cache::put($cacheKey, $certs, now()->addMinutes(55));
            return $certs;
        } catch (\Throwable $exception) {
            report($exception);
            return [];
        }
    }

    // Reads Firebase Phone Auth setup from admin settings with env fallback.
    private function getFirebasePhoneAuthConfig(): array
    {
        $setting = businessConfig(FIREBASE_PHONE_AUTH, FIREBASE_PHONE_AUTH)?->value;
        return is_array($setting) ? $setting : [];
    }

    // Validates Firebase Auth token claims for this project and phone auth flow.
    private function firebasePhoneClaimsAreValid(array $payload, string $projectId): bool
    {
        $now = time();

        return ($payload['aud'] ?? null) === $projectId
            && ($payload['iss'] ?? null) === "https://securetoken.google.com/{$projectId}"
            && !empty($payload['sub'])
            && is_string($payload['sub'])
            && strlen($payload['sub']) <= 128
            && !empty($payload['phone_number'])
            && is_string($payload['phone_number'])
            && is_numeric($payload['exp'] ?? null)
            && (int)$payload['exp'] > $now
            && is_numeric($payload['iat'] ?? null)
            && (int)$payload['iat'] <= ($now + 60);
    }

    // Decodes a base64url JWT segment into its raw string value.
    private function base64UrlDecode(string $value): string
    {
        $value = strtr($value, '-_', '+/');
        $padding = strlen($value) % 4;
        if ($padding) {
            $value .= str_repeat('=', 4 - $padding);
        }

        $decoded = base64_decode($value, true);
        return $decoded === false ? '' : $decoded;
    }
}
