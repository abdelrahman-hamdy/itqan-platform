<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Tracks admin decisions captured via the temporary
 * /manage/admin-audit page during the subscription-system cleanup.
 *
 * Each row is one admin's verdict on one case (case_key is unique).
 * Re-submitting the same case_key updates the existing row.
 */
class SubscriptionAdminAuditDecision extends Model
{
    use HasFactory;

    protected $fillable = [
        'case_type',
        'subject_type',
        'subject_id',
        'case_key',
        'selected_option',
        'free_text',
        'decided_by_user_id',
        'decided_at',
        'applied_at',
        'applied_by_user_id',
        'applied_outcome',
    ];

    protected $casts = [
        'subject_id' => 'integer',
        'decided_by_user_id' => 'integer',
        'applied_by_user_id' => 'integer',
        'decided_at' => 'datetime',
        'applied_at' => 'datetime',
    ];

    public function decidedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'decided_by_user_id');
    }
}
