<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\Pivot;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Enums\RelationshipType;

class ParentStudentRelationship extends Pivot
{
    /**
     * The table associated with the pivot model.
     */
    protected $table = 'parent_student_relationships';

    /**
     * The attributes that are mass assignable.
     * SECURITY: Explicitly define fillable fields to prevent mass assignment vulnerabilities.
     */
    protected $fillable = [
        'parent_id',
        'student_id',
        'relationship_type',
    ];

    /**
     * The attributes that should be cast.
     */
    protected $casts = [
        'relationship_type' => RelationshipType::class,
    ];

    /**
     * Indicates if the IDs are auto-incrementing.
     */
    public $incrementing = true;

    /**
     * Get the parent profile for this relationship.
     */
    public function parent(): BelongsTo
    {
        return $this->belongsTo(ParentProfile::class, 'parent_id');
    }

    /**
     * Get the student profile for this relationship.
     */
    public function student(): BelongsTo
    {
        return $this->belongsTo(StudentProfile::class, 'student_id');
    }
}
