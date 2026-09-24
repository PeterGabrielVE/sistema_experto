<?php

namespace App\Http\Requests;

use App\Rules\Rut;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class PatientRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('manage-patients');
    }

    protected function prepareForValidation(): void
    {
        $data = [];

        foreach (['first_name', 'last_name', 'address', 'comment'] as $field) {
            if ($this->has($field)) {
                $data[$field] = trim((string) $this->input($field));
            }
        }

        // Stored normalized (12345678-K) so uniqueness is checked on one format.
        if ($this->filled('rut')) {
            $data['rut'] = Rut::normalize($this->input('rut'));
        }

        $this->merge($data);
    }

    public function rules(): array
    {
        $patient = $this->route('patient');

        return [
            'first_name' => ['required', 'string', 'min:3', 'max:100'],
            'last_name' => ['required', 'string', 'min:3', 'max:100'],
            // The RUT is set on registration; the edit form does not send it.
            'rut' => [
                $patient ? 'sometimes' : 'required',
                'string',
                new Rut,
                Rule::unique('patients', 'rut')->ignore($patient),
            ],
            'address' => ['required', 'string', 'min:3', 'max:255'],
            'birthdate' => ['required', 'date', 'before:today', 'after:1900-01-01'],
            'gender' => ['required', Rule::in(['H', 'M'])],
            'comment' => ['nullable', 'string', 'max:255'],
            'image' => ['nullable', 'image', 'mimes:jpg,jpeg,png', 'max:2048'],
        ];
    }

    public function attributes(): array
    {
        return [
            'first_name' => 'nombre',
            'last_name' => 'apellido',
            'rut' => 'RUT',
            'address' => 'dirección',
            'birthdate' => 'fecha de nacimiento',
            'gender' => 'sexo',
            'comment' => 'comentario',
            'image' => 'imagen',
        ];
    }

    public function messages(): array
    {
        return [
            'rut.unique' => __('Ya existe un paciente con este RUT.'),
            'birthdate.before' => __('La fecha de nacimiento debe ser anterior a hoy.'),
            'birthdate.after' => __('La fecha de nacimiento no es válida.'),
        ];
    }
}
