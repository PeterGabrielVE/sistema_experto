<?php

namespace App\Http\Resources;

use App\Models\Patient;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Patient
 */
class PatientResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'first_name' => $this->first_name,
            'last_name' => $this->last_name,
            'full_name' => $this->fullName(),
            'rut' => $this->rut,
            'email' => $this->email,
            'address' => $this->address,
            'birthdate' => $this->birthdate?->toDateString(),
            'age' => $this->age,
            'gender' => $this->gender,
            'gender_label' => $this->genderLabel(),
            'comment' => $this->comment,
            'links' => [
                'edit' => route('patient.edit', $this->resource),
                'clinical_record' => route('patient.clinical-record.show', $this->resource),
            ],
        ];
    }
}
