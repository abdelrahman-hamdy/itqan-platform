<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Support Ticket Reply Model
 *
 * Represents a reply on a support ticket from either the reporter or an admin/supervisor.
 */
class SupportTicketReply extends Model
{
    protected $fillable = [
        'support_ticket_id',
        'user_id',
        'body',
    ];

    // ========================================
    // Relationships
    // ========================================

    public function ticket(): BelongsTo
    {
        return $this->belongsTo(SupportTicket::class, 'support_ticket_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
