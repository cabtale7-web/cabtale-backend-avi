<?php

namespace Modules\BusinessManagement\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;

class FirebasePhoneAuthSetupStoreOrUpdateRequest extends FormRequest
{
    // Validates Firebase Phone Auth setup fields submitted from admin panel.
    public function rules(): array
    {
        return [
            'status' => 'nullable|in:on,1',
            'project_id' => 'required|string|max:191',
            'auth_cert_url' => 'nullable|url|max:500',
        ];
    }

    // Allows authenticated admin users to submit Firebase Phone Auth setup.
    public function authorize(): bool
    {
        return Auth::check();
    }
}
