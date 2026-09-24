<?php

namespace App\Events;

use App\Models\Diagnosis;
use App\Models\User;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * A doctor confirmed or corrected the category proposed by the inference engine.
 */
class DiagnosisCategoryConfirmed
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public Diagnosis $diagnosis,
        public User $actor,
        public ?int $previousRule,
        public ?string $previousSource,
    ) {
    }

    public function changedCategory(): bool
    {
        return $this->previousRule !== (int) $this->diagnosis->id_rule;
    }
}
