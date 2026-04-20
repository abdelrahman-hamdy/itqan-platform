<?php

namespace App\Models;

use App\Models\Traits\ScopedToAcademy;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Audit entry for a single alarm attempt from one session participant to another.
 *
 * Created by SessionAlarmService whenever an alarm is dispatched — including
 * cooldown-rejected attempts — so the table doubles as a rate-limit trail.
 */
class SessionAlarm extends Model
{
    use ScopedToAcademy;

    public const UPDATED_AT = null;

    protected $fillable = [
        'academy_id',
        'call_id',
        'session_type',
        'session_id',
        'caller_id',
        'target_id',
        'answered_at',
        'declined_at',
        'cancelled_at',
    ];

    protected $casts = [
        'created_at' => 'datetime',
        'answered_at' => 'datetime',
        'declined_at' => 'datetime',
        'cancelled_at' => 'datetime',
    ];

    public function caller(): BelongsTo
    {
        return $this->belongsTo(User::class, 'caller_id');
    }

    public function target(): BelongsTo
    {
        return $this->belongsTo(User::class, 'target_id');
    }
}
