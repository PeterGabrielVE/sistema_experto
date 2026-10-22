<?php

namespace App\Events;

use App\Models\Patient;
use App\Models\User;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * The portal access of a patient changed: invited, activated, logged in or revoked (US-6.3/AC-9).
 */
class PatientPortalAccessChanged
{
    use Dispatchable, SerializesModels;

    public const INVITED = 'invited';

    public const ACTIVATED = 'activated';

    public const LOGIN = 'login';

    public const REVOKED = 'revoked';

    /**
     * @param  array<string, int|string|null>  $context  Identifiers only: no clinical or contact data.
     */
    public function __construct(
        public Patient $patient,
        public User $actor,
        public string $action,
        public array $context = [],
    ) {}
}
