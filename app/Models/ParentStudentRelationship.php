<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\Pivot;
use App\Enums\RelationshipType;

class ParentStudentRelationship extends Pivot
{
    /**
     * The table associated with the pivot model.
     */
    protected $table = 'parent_student_relationships';

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
}
