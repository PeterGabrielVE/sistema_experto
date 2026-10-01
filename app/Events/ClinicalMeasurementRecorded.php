<?php

namespace App\Events;

use App\Models\ClinicalMeasurement;
use App\Models\User;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * A clinical measurement was created, updated or deleted.
 */
class ClinicalMeasurementRecorded
{
    use Dispatchable, SerializesModels;

    public const CREATED = 'created';

    public const UPDATED = 'updated';

    public const DELETED = 'deleted';

    /**
     * @param  array<int, string>  $changedFields  Names only: values are sensitive health data.
     */
    public function __construct(
        public ClinicalMeasurement $measurement,
        public User $actor,
        public string $action,
        public array $changedFields = [],
    ) {
    }
}
