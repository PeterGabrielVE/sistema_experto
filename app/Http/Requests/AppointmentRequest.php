<?php

namespace App\Http\Requests;

use App\Rules\FutureClinicDateTime;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Carbon;

class AppointmentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('update', $this->route('patient'));
    }

    public function rules(): array
    {
        return [
            'scheduled_at' => ['required', 'string', new FutureClinicDateTime],
            'notes' => ['nullable', 'string', 'max:500'],
        ];
    }

    public function attributes(): array
    {
        return [
            'scheduled_at' => 'próxima consulta',
            'notes' => 'indicaciones',
        ];
    }

    /**
     * The validated date and time, in UTC.
     */
    public function scheduledAt(): Carbon
    {
        return FutureClinicDateTime::parse($this->validated('scheduled_at'));
    }
}
