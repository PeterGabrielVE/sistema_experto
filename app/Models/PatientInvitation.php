<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * Invitation of a patient to the portal (US-6.3).
 *
 * @property int $patient_id
 * @property int|null $invited_by
 * @property string $email
 * @property string|null $token_hash
 * @property Carbon $expires_at
 * @property string $status
 * @property Carbon|null $sent_at
 * @property Carbon|null $failed_at
 * @property Carbon|null $accepted_at
 */
class PatientInvitation extends Model
{
    public const QUEUED = 'queued';

    public const SENT = 'sent';

    public const FAILED = 'failed';

    public const ACCEPTED = 'accepted';

    public const REVOKED = 'revoked';

    protected $fillable = [
        'patient_id', 'invited_by', 'email', 'token_hash', 'expires_at', 'status', 'sent_at', 'failed_at', 'accepted_at',
    ];

    protected function casts(): array
    {
        return [
            'expires_at' => 'datetime',
            'sent_at' => 'datetime',
            'failed_at' => 'datetime',
            'accepted_at' => 'datetime',
        ];
    }

    public function patient(): BelongsTo
    {
        return $this->belongsTo(Patient::class);
    }

    public function inviter(): BelongsTo
    {
        return $this->belongsTo(User::class, 'invited_by');
    }

    public function isExpired(): bool
    {
        return $this->expires_at->isPast();
    }

    /**
     * Link of the welcome mail.
     */
    public static function url(string $token): string
    {
        return route('portal.invitation', $token);
    }
}
