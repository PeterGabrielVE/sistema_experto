<?php

namespace App\Services;

use App\Events\PatientDeleted;
use App\Events\PatientRegistered;
use App\Models\Diagnosis;
use App\Models\Patient;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;

class PatientService
{
    public const DEFAULT_IMAGE = '0.jpg';

    public function register(array $data, User $author, ?UploadedFile $image = null): Patient
    {
        $patient = DB::transaction(function () use ($data, $author, $image) {
            $patient = Patient::create([
                ...$data,
                'created_by' => $author->id,
                'image' => self::DEFAULT_IMAGE,
            ]);

            if ($image) {
                $patient->update(['image' => $this->storeImage($patient, $image)]);
            }

            return $patient;
        });

        PatientRegistered::dispatch($patient, $author);

        return $patient;
    }

    public function update(Patient $patient, array $data): Patient
    {
        $patient->update($data);

        return $patient;
    }

    /**
     * Diagnoses are removed by the ON DELETE CASCADE foreign key.
     */
    public function delete(Patient $patient, User $actor): void
    {
        $diagnoses = Diagnosis::where('id_patient', $patient->id)->count();

        $patient->delete();

        PatientDeleted::dispatch($patient->id, $patient->rut, $diagnoses, $actor);
    }

    /**
     * The file name is derived from the patient id, never from user input.
     */
    private function storeImage(Patient $patient, UploadedFile $image): string
    {
        $fileName = $patient->id.'.'.$image->extension();
        $image->move(public_path('patient/images'), $fileName);

        return $fileName;
    }
}
