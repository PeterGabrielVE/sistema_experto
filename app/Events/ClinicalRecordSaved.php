<?php

namespace App\Events;

use App\Models\ClinicalRecord;
use App\Models\User;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class ClinicalRecordSaved
{
    use Dispatchable, SerializesModels;

    /**
     * @param  array<int, string>  $changedFields  Names only: values are sensitive health data.
     */
    public function __construct(
        public ClinicalRecord $record,
        public User $actor,
        public bool $created,
        public array $changedFields,
    ) {
    }
}
