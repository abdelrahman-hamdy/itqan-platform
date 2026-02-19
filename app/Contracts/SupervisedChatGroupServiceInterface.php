<?php

namespace App\Contracts;

use App\Models\AcademicIndividualLesson;
use App\Models\ChatGroup;
use App\Models\InteractiveCourse;
use App\Models\QuranCircle;
use App\Models\QuranIndividualCircle;
use App\Models\User;

interface SupervisedChatGroupServiceInterface
{
    public function isChatAvailable(User $teacher): bool;

    public function getOrCreateSupervisedQuranIndividualGroup(QuranIndividualCircle $individualCircle): ?ChatGroup;

    public function getOrCreateSupervisedAcademicLessonGroup(AcademicIndividualLesson $lesson): ?ChatGroup;

    public function getOrCreateSupervisedQuranCircleGroup(QuranCircle $circle): ?ChatGroup;

    public function getOrCreateSupervisedInteractiveCourseGroup(InteractiveCourse $course): ?ChatGroup;

    public function getOrCreateSupervisedChat(
        User $teacher,
        User $student,
        string $entityType,
        int $entityId
    ): ?ChatGroup;

    public function replaceSupervisor(ChatGroup $group, ?User $oldSupervisor, User $newSupervisor): void;

    public function addSupervisorToTeacherGroups(User $teacher, User $supervisor): int;
}
