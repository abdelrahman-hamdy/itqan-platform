<?php

namespace App\Models;

use App\Enums\BusinessRequestStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BusinessServiceRequest extends Model
{
    use HasFactory;

    protected $fillable = [
        'client_name',
        'client_phone',
        'client_email',
        'service_category_id',
        'project_budget',
        'project_deadline',
        'project_description',
        'status',
        'admin_notes',
    ];

    protected $casts = [
        'status' => BusinessRequestStatus::class,
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Get the service category for this request.
     */
    public function serviceCategory(): BelongsTo
    {
        return $this->belongsTo(BusinessServiceCategory::class, 'service_category_id');
    }

    /**
     * Scope to get requests by status.
     */
    public function scopeByStatus($query, $status)
    {
        return $query->where('status', $status);
    }

    /**
     * Get status label in Arabic.
     */
    public function getStatusLabelAttribute(): string
    {
        return match($this->status) {
            'pending' => 'في الانتظار',
            'reviewed' => 'تم المراجعة',
            'approved' => 'مقبول',
            'rejected' => 'مرفوض',
            'completed' => 'مكتمل',
            default => 'غير محدد',
        };
    }

    /**
     * Get status color for display.
     */
    public function getStatusColorAttribute(): string
    {
        return match($this->status) {
            'pending' => 'yellow',
            'reviewed' => 'blue',
            'approved' => 'green',
            'rejected' => 'red',
            'completed' => 'gray',
            default => 'gray',
        };
    }
}
