<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Facades\Storage;
use Throwable;

class TestConnectionsCommand extends Command
{
    protected $signature = 'test:connections
                            {--with-storage : Also test configured filesystem disk write/read/delete}';

    protected $description = 'Validate deployment readiness (env, CSS assets, DB, Redis/cache/session/queue)';

    private int $failures = 0;
    private int $warnings = 0;

    public function handle(): int
    {
        $this->line('Starting deployment readiness checks...');
        $this->newLine();

        $this->checkEnvironment();
        $this->checkAssetFiles();
        $this->checkDatabase();
        $this->checkRedisAndCache();
        $this->checkSessionDriver();
        $this->checkQueueConnection();

        if ($this->option('with-storage')) {
            $this->checkStorageDisk();
        } else {
            $this->warnLine('Storage check skipped. Use --with-storage if you want disk write/read verification.');
        }

        $this->newLine();
        $this->line(str_repeat('-', 70));
        $this->line("Failures: {$this->failures} | Warnings: {$this->warnings}");

        if ($this->failures > 0) {
            $this->error('Deployment readiness check failed.');
            return self::FAILURE;
        }

        $this->info('Deployment readiness check passed.');
        return self::SUCCESS;
    }

    private function checkEnvironment(): void
    {
        $this->line('Environment checks:');

        $appKey = (string) config('app.key');
        $appUrl = (string) config('app.url');
        $assetUrl = (string) config('app.asset_url');
        $appEnv = (string) config('app.env');
        $isDebug = (bool) config('app.debug');

        if ($appKey !== '') {
            $this->passLine('APP_KEY is set.');
        } else {
            $this->failLine('APP_KEY is missing.');
        }

        if ($this->hasHttpScheme($appUrl)) {
            $this->passLine("APP_URL looks valid: {$appUrl}");
        } else {
            $this->failLine('APP_URL must include http:// or https:// (this commonly breaks CSS/JS URLs).');
        }

        if ($assetUrl === '') {
            $this->warnLine('ASSET_URL is empty. This is allowed, but set it explicitly in production if assets use a different host.');
        } elseif ($this->hasHttpScheme($assetUrl)) {
            $this->passLine("ASSET_URL looks valid: {$assetUrl}");
        } else {
            $this->failLine('ASSET_URL is set but missing http:// or https://.');
        }

        if ($appEnv === 'production' && $isDebug) {
            $this->failLine('APP_DEBUG is true in production.');
        } else {
            $this->passLine("APP_ENV={$appEnv}, APP_DEBUG=" . ($isDebug ? 'true' : 'false'));
        }

        $this->passLine('CACHE_DRIVER=' . config('cache.default'));
        $this->passLine('SESSION_DRIVER=' . config('session.driver'));
        $this->passLine('QUEUE_CONNECTION=' . config('queue.default'));
        $this->passLine('FORCE_HTTPS=' . (filter_var(config('app.force_https'), FILTER_VALIDATE_BOOLEAN) ? 'true' : 'false'));
    }

    private function checkAssetFiles(): void
    {
        $this->line('Asset file checks:');

        $paths = [
            'css/app.css' => public_path('css/app.css'),
            'assets/admin-module/css/style.css' => public_path('assets/admin-module/css/style.css'),
            'landing-page/assets/css/main.css' => public_path('landing-page/assets/css/main.css'),
        ];

        foreach ($paths as $label => $path) {
            if (is_file($path)) {
                $this->passLine("Found {$label}");
            } else {
                $this->failLine("Missing {$label} at {$path}");
            }
        }
    }

    private function checkDatabase(): void
    {
        $this->line('Database check:');

        try {
            DB::connection()->getPdo();
            $this->passLine('Database connection succeeded.');
        } catch (Throwable $e) {
            $this->failLine('Database connection failed: ' . $e->getMessage());
        }
    }

    private function checkRedisAndCache(): void
    {
        $this->line('Redis / cache checks:');

        $cacheDriver = (string) Config::get('cache.default', 'file');
        $sessionDriver = (string) Config::get('session.driver', 'file');
        $queueConnection = (string) Config::get('queue.default', 'sync');
        $usesRedis = in_array('redis', [$cacheDriver, $sessionDriver, $queueConnection], true);

        if ($usesRedis) {
            $redisClient = (string) Config::get('database.redis.client', 'phpredis');

            if ($redisClient === 'phpredis' && !extension_loaded('redis')) {
                $this->failLine('REDIS_CLIENT=phpredis but PHP redis extension is not loaded.');
            } else {
                $this->passLine("Redis client configured: {$redisClient}");
            }

            $this->checkRedisConnection('default');
            $this->checkRedisConnection('cache');
        } else {
            $this->warnLine('Redis is not used by cache/session/queue in current configuration.');
        }

        if ($cacheDriver === 'memcached' && !extension_loaded('memcached')) {
            $this->failLine('CACHE_DRIVER=memcached but PHP memcached extension is not loaded.');
            return;
        }

        $cacheKey = 'deployment_check_cache_' . uniqid('', true);
        try {
            Cache::store($cacheDriver)->put($cacheKey, 'ok', 60);
            $value = Cache::store($cacheDriver)->get($cacheKey);
            Cache::store($cacheDriver)->forget($cacheKey);

            if ($value === 'ok') {
                $this->passLine("Cache read/write succeeded on store '{$cacheDriver}'.");
            } else {
                $this->failLine("Cache read/write failed on store '{$cacheDriver}'.");
            }
        } catch (Throwable $e) {
            $this->failLine("Cache store '{$cacheDriver}' failed: " . $e->getMessage());
        }
    }

    private function checkSessionDriver(): void
    {
        $this->line('Session checks:');

        $sessionDriver = (string) Config::get('session.driver', 'file');
        $supported = ['file', 'cookie', 'database', 'apc', 'memcached', 'redis', 'dynamodb', 'array'];

        if (in_array($sessionDriver, $supported, true)) {
            $this->passLine("Session driver '{$sessionDriver}' is valid.");
        } else {
            $this->failLine("Session driver '{$sessionDriver}' is invalid.");
        }

        if ($sessionDriver === 'memcached' && !extension_loaded('memcached')) {
            $this->failLine('SESSION_DRIVER=memcached but PHP memcached extension is not loaded.');
        }

        if ($sessionDriver === 'redis') {
            $connection = Config::get('session.connection') ?: 'default';
            $this->checkRedisConnection((string) $connection, 'Session');
        }
    }

    private function checkQueueConnection(): void
    {
        $this->line('Queue checks:');

        $defaultConnection = (string) Config::get('queue.default', 'sync');
        $queueConfig = Config::get("queue.connections.{$defaultConnection}");

        if (!is_array($queueConfig)) {
            $this->failLine("Queue connection '{$defaultConnection}' is not configured.");
            return;
        }

        $driver = (string) ($queueConfig['driver'] ?? 'unknown');
        $this->passLine("Queue connection '{$defaultConnection}' uses driver '{$driver}'.");

        if ($driver === 'redis') {
            $redisConnection = (string) ($queueConfig['connection'] ?? 'default');
            $this->checkRedisConnection($redisConnection, 'Queue');
        }
    }

    private function checkStorageDisk(): void
    {
        $this->line('Storage checks:');

        $disk = (string) Config::get('filesystems.default', 'local');
        $path = 'deployment-check/' . now()->format('Ymd_His') . '.txt';
        $content = 'deployment-check-' . now()->toDateTimeString();

        try {
            Storage::disk($disk)->put($path, $content);
            $readBack = Storage::disk($disk)->get($path);
            Storage::disk($disk)->delete($path);

            if ($readBack === $content) {
                $this->passLine("Storage disk '{$disk}' write/read/delete succeeded.");
            } else {
                $this->failLine("Storage disk '{$disk}' read-back content mismatch.");
            }
        } catch (Throwable $e) {
            $this->failLine("Storage disk '{$disk}' check failed: " . $e->getMessage());
        }
    }

    private function checkRedisConnection(string $connection, string $context = 'Redis'): void
    {
        try {
            $response = Redis::connection($connection)->ping();
            if ($response === true || strtoupper((string) $response) === 'PONG') {
                $this->passLine("{$context} connection '{$connection}' responded to PING.");
                return;
            }

            $this->warnLine("{$context} connection '{$connection}' ping returned an unexpected response.");
        } catch (Throwable $e) {
            $this->failLine("{$context} connection '{$connection}' failed: " . $e->getMessage());
        }
    }

    private function hasHttpScheme(string $url): bool
    {
        $scheme = parse_url($url, PHP_URL_SCHEME);
        return in_array($scheme, ['http', 'https'], true);
    }

    private function passLine(string $message): void
    {
        $this->info("[PASS] {$message}");
    }

    private function failLine(string $message): void
    {
        $this->failures++;
        $this->error("[FAIL] {$message}");
    }

    private function warnLine(string $message): void
    {
        $this->warnings++;
        $this->warn("[WARN] {$message}");
    }
}
