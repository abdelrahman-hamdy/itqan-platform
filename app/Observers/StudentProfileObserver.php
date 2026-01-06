<?php

namespace App\Observers;

use App\Models\StudentProfile;

class StudentProfileObserver
{
    /**
     * Handle the StudentProfile "created" event.
     */
    public function created(StudentProfile $studentProfile): void
    {
        //
    }

    /**
     * Handle the StudentProfile "updated" event.
     */
    public function updated(StudentProfile $studentProfile): void
    {
        // Sync parent-student relationship when parent_id changes
        if ($studentProfile->isDirty('parent_id')) {
            $oldParentId = $studentProfile->getOriginal('parent_id');
            $newParentId = $studentProfile->parent_id;

            // Remove from old parent's students relationship
            if ($oldParentId) {
                $oldParent = \App\Models\ParentProfile::find($oldParentId);
                if ($oldParent) {
                    $oldParent->students()->detach($studentProfile->id);
                }
            }

            // Add to new parent's students relationship
            if ($newParentId) {
                $newParent = \App\Models\ParentProfile::find($newParentId);
                if ($newParent && ! $newParent->students()->where('student_profiles.id', $studentProfile->id)->exists()) {
                    $newParent->students()->attach($studentProfile->id, [
                        'relationship_type' => 'other', // Default relationship type
                    ]);
                }
            }
        }
    }

    /**
     * Handle the StudentProfile "deleted" event.
     */
    public function deleted(StudentProfile $studentProfile): void
    {
        // Remove student from parent's students relationship when deleted
        if ($studentProfile->parent_id) {
            $parent = \App\Models\ParentProfile::find($studentProfile->parent_id);
            if ($parent) {
                $parent->students()->detach($studentProfile->id);
            }
        }
    }

    /**
     * Handle the StudentProfile "restored" event.
     */
    public function restored(StudentProfile $studentProfile): void
    {
        //
    }

    /**
     * Handle the StudentProfile "force deleted" event.
     */
    public function forceDeleted(StudentProfile $studentProfile): void
    {
        //
    }
}
