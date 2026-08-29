<?php

declare(strict_types=1);

namespace App\Http\Requests\Install;

use Illuminate\Foundation\Http\FormRequest;

class DatabaseConnectionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, array<int, string>>
     */
    public function rules(): array
    {
        return [
            'db_host' => ['required', 'string', 'max:255'],
            'db_port' => ['required', 'integer', 'min:1', 'max:65535'],
            'db_database' => ['required', 'string', 'max:64'],
            'db_username' => ['required', 'string'],
            'db_password' => ['nullable', 'string'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'db_host' => 'database host',
            'db_port' => 'database port',
            'db_database' => 'database name',
            'db_username' => 'database username',
            'db_password' => 'database password',
        ];
    }

    /**
     * @return array{host: string, port: int, database: string, username: string, password: string|null}
     */
    public function credentials(): array
    {
        return [
            'host' => (string) $this->input('db_host'),
            'port' => (int) $this->input('db_port'),
            'database' => (string) $this->input('db_database'),
            'username' => (string) $this->input('db_username'),
            'password' => $this->filled('db_password') ? (string) $this->input('db_password') : null,
        ];
    }
}
