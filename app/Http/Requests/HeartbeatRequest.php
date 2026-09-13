<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class HeartbeatRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'duration_seconds' => ['required', 'integer', 'between:0,86400'],
            'max_scroll_depth' => ['sometimes', 'integer', 'between:0,100'],
            'landing_source' => ['sometimes', 'nullable', 'string', 'max:255'],
        ];
    }
}
