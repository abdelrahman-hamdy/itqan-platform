<?php

namespace App\Http\Resources\Api\V1\Circle;

use App\Models\QuranCircle;
use App\Models\QuranIndividualCircle;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Circle List Resource (Minimal)
 *
 * Minimal circle data for listings.
 *
 * @mixin QuranCircle|QuranIndividualCircle
 */
class CircleListResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $isIndividual = $this->resource instanceof QuranIndividualCircle;

        return [
            'id' => $this->resource->id,
            'type' => $isIndividual ? 'individual' : 'group',
            'circle_name' => $this->resource->circle_name,
            'teacher_name' => $this->resource->quranTeacher?->user?->name,
            'is_active' => $this->resource->is_active,
            'students_count' => $isIndividual ? 1 : $this->resource->current_students,
        ];
    }
}
