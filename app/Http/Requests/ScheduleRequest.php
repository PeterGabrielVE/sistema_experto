<?php

namespace App\Http\Requests;

use App\Models\Schedule;
use Illuminate\Foundation\Http\FormRequest;

class ScheduleRequest extends FormRequest
{
    public function authorize(): bool
    {
        $schedule = $this->route('schedule');

        return $schedule
            ? $this->user()->can('update', $schedule)
            : $this->user()->can('create', Schedule::class);
    }

    public function rules(): array
    {
        return [
            'breakfast' => ['required', 'date_format:H:i'],
            'lunch' => ['required', 'date_format:H:i', 'after:breakfast'],
            'dinner' => ['required', 'date_format:H:i', 'after:lunch'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ];
    }

    public function attributes(): array
    {
        return [
            'breakfast' => 'desayuno',
            'lunch' => 'almuerzo',
            'dinner' => 'cena',
            'notes' => 'notas',
        ];
    }

    /**
     * notes is NOT NULL in the schedules table.
     */
    public function validated($key = null, $default = null)
    {
        $data = parent::validated($key, $default);

        if ($key === null) {
            $data['notes'] ??= '';
        }

        return $data;
    }
}
