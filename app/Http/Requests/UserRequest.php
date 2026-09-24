<?php

namespace App\Http\Requests;

use App\Enums\Role;
use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Illuminate\Validation\Validator;

class UserRequest extends FormRequest
{
    public function authorize(): bool
    {
        $user = $this->route('user');

        return $user
            ? $this->user()->can('update', $user)
            : $this->user()->can('create', User::class);
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'name' => trim((string) $this->name),
            'email' => mb_strtolower(trim((string) $this->email)),
        ]);
    }

    public function rules(): array
    {
        $user = $this->route('user');

        return [
            'name' => ['required', 'string', 'min:3', 'max:255'],
            'email' => [
                'required', 'string', 'email:rfc', 'max:255',
                Rule::unique('users', 'email')->ignore($user?->id),
            ],
            'rol_id' => ['required', Rule::enum(Role::class)],
            'password' => [
                $user ? 'nullable' : 'required',
                'confirmed',
                Password::min(8)->letters()->numbers(),
            ],
        ];
    }

    /**
     * An administrator cannot change their own role; this guarantees the
     * system never runs out of administrators.
     */
    public function after(): array
    {
        return [
            function (Validator $validator) {
                $user = $this->route('user');

                if ($user && $user->is($this->user()) && (int) $this->rol_id !== (int) $user->rol_id) {
                    $validator->errors()->add('rol_id', __('No puede cambiar su propio rol.'));
                }
            },
        ];
    }

    public function attributes(): array
    {
        return [
            'name' => 'nombre',
            'email' => 'correo',
            'rol_id' => 'rol',
            'password' => 'contraseña',
        ];
    }
}
