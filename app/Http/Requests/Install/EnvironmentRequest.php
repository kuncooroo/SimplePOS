<?php

declare(strict_types=1);

namespace App\Http\Requests\Install;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class EnvironmentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'app_debug' => $this->boolean('app_debug'),
            'app_env' => $this->input('app_env', 'production'),
        ]);
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'app_url' => ['required', 'url'],
            'timezone' => ['required', 'string', Rule::in(timezone_identifiers_list())],
            'app_env' => ['required', 'string', Rule::in(['production', 'local'])],
            'app_debug' => ['boolean'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'app_url' => 'application URL',
            'timezone' => 'timezone',
            'app_env' => 'application environment',
            'app_debug' => 'debug mode',
        ];
    }

    /**
     * @return array{app_url: string, timezone: string, app_env: string, app_debug: bool}
     */
    public function applicationSettings(): array
    {
        return [
            'app_url' => (string) $this->input('app_url'),
            'timezone' => (string) $this->input('timezone'),
            'app_env' => (string) $this->input('app_env', 'production'),
            'app_debug' => $this->boolean('app_debug'),
        ];
    }
}
