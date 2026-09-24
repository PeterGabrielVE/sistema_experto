<?php

namespace App\Listeners;

use App\Events\ClinicalRecordSaved;
use App\Events\DiagnosisCategoryConfirmed;
use App\Events\DiagnosisCreated;
use App\Events\PatientDeleted;
use App\Events\PatientRegistered;
use App\Events\UserAccountCreated;
use App\Events\UserRoleChanged;
use App\Models\User;
use Illuminate\Support\Facades\Log;

/**
 * Clinical audit trail: who did what, on which record, when.
 * Written to the "audit" log channel (storage/logs/audit-YYYY-MM-DD.log).
 * Runs synchronously on purpose: an audit entry must not be lost in a queue.
 * Personal data is limited to identifiers.
 */
class RecordAuditTrail
{
    public function handlePatientRegistered(PatientRegistered $event): void
    {
        $this->record('patient.registered', $event->actor, ['patient_id' => $event->patient->id]);
    }

    public function handlePatientDeleted(PatientDeleted $event): void
    {
        $this->record('patient.deleted', $event->actor, [
            'patient_id' => $event->patientId,
            'rut' => $event->rut,
            'diagnoses_deleted' => $event->diagnosesDeleted,
        ]);
    }

    public function handleClinicalRecordSaved(ClinicalRecordSaved $event): void
    {
        $this->record($event->created ? 'clinical_record.created' : 'clinical_record.updated', $event->actor, [
            'patient_id' => $event->record->patient_id,
            'clinical_record_id' => $event->record->id,
            'fields' => $event->changedFields,
        ]);
    }

    public function handleDiagnosisCreated(DiagnosisCreated $event): void
    {
        $this->record('diagnosis.created', $event->actor, [
            'diagnosis_id' => $event->diagnosis->id,
            'patient_id' => $event->diagnosis->id_patient,
            'category' => $event->diagnosis->id_rule,
            'source' => $event->diagnosis->inference_source,
            'model_version' => $event->diagnosis->model_version,
        ]);
    }

    public function handleDiagnosisCategoryConfirmed(DiagnosisCategoryConfirmed $event): void
    {
        $this->record('diagnosis.category_confirmed', $event->actor, [
            'diagnosis_id' => $event->diagnosis->id,
            'from' => $event->previousRule,
            'from_source' => $event->previousSource,
            'to' => $event->diagnosis->id_rule,
            'changed' => $event->changedCategory(),
        ]);
    }

    public function handleUserAccountCreated(UserAccountCreated $event): void
    {
        $this->record('user.created', $event->actor, [
            'user_id' => $event->user->id,
            'role' => $event->user->role()?->name,
        ]);
    }

    public function handleUserRoleChanged(UserRoleChanged $event): void
    {
        $this->record('user.role_changed', $event->actor, [
            'user_id' => $event->user->id,
            'from' => $event->from?->name,
            'to' => $event->to?->name,
        ]);
    }

    private function record(string $action, User $actor, array $context): void
    {
        Log::channel('audit')->info($action, [
            'actor_id' => $actor->id,
            'actor_role' => $actor->role()?->name,
            'ip' => request()?->ip(),
            ...$context,
        ]);
    }
}
