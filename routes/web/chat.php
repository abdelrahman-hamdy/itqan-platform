<?php

/*
|--------------------------------------------------------------------------
| Chat Routes (WireChat)
|--------------------------------------------------------------------------
| Override WireChat package routes to provide Arabic titles and subdomain support.
*/

use Illuminate\Support\Facades\Route;

Route::domain('{subdomain}.'.config('app.domain'))->group(function () {

    Route::middleware(config('wirechat.routes.middleware'))
        ->prefix(config('wirechat.routes.prefix'))
        ->group(function () {
            Route::get('/', \App\Livewire\Pages\Chats::class)->name('chats');

            Route::get('/start-with/{user}', function ($subdomain, \App\Models\User $user) {
                // Log the attempt for debugging
                \Log::info('Chat start-with route called', [
                    'subdomain' => $subdomain,
                    'auth_user_id' => auth()->id(),
                    'target_user_id' => $user->id,
                    'target_user_name' => $user->name,
                ]);

                // Get or create conversation with the specified user
                $conversation = auth()->user()->getOrCreatePrivateConversation($user);

                if (! $conversation) {
                    \Log::error('Failed to create conversation in route', [
                        'auth_user_id' => auth()->id(),
                        'target_user_id' => $user->id,
                    ]);

                    // If conversation creation fails, redirect to chats list with error
                    return redirect()->route('chats', ['subdomain' => $subdomain])
                        ->with('error', 'حدث خطأ في إنشاء المحادثة. يرجى المحاولة لاحقاً.');
                }

                \Log::info('Conversation created/found successfully', [
                    'conversation_id' => $conversation->id,
                ]);

                return redirect()->route('chat', [
                    'subdomain' => $subdomain,
                    'conversation' => $conversation->id,
                ]);
            })->middleware(\App\Http\Middleware\BlockPrivateTeacherStudentChat::class)->name('chat.start-with');

            // Route for starting supervised group chats (teacher + student + supervisor)
            Route::get('/start-supervised/{teacher}/{student}/{entityType}/{entityId}', function (
                $subdomain,
                \App\Models\User $teacher,
                \App\Models\User $student,
                string $entityType,
                int $entityId
            ) {
                \Log::info('Supervised chat start route called', [
                    'subdomain' => $subdomain,
                    'auth_user_id' => auth()->id(),
                    'teacher_id' => $teacher->id,
                    'student_id' => $student->id,
                    'entity_type' => $entityType,
                    'entity_id' => $entityId,
                ]);

                // Validate current user is authorized (teacher, student, or supervisor)
                $currentUser = auth()->user();
                $supervisor = $teacher->getPrimarySupervisor();

                if (! in_array($currentUser->id, [$teacher->id, $student->id, $supervisor?->id])) {
                    if (! in_array($currentUser->user_type, ['supervisor', 'admin', 'super_admin'])) {
                        \Log::warning('Unauthorized supervised chat access attempt', [
                            'current_user_id' => $currentUser->id,
                            'teacher_id' => $teacher->id,
                            'student_id' => $student->id,
                        ]);

                        return redirect()->route('chats', ['subdomain' => $subdomain])
                            ->with('error', 'غير مصرح لك بالوصول لهذه المحادثة.');
                    }
                }

                // Get or create the supervised chat group
                $chatGroupService = app(\App\Services\SupervisedChatGroupService::class);

                if (! $chatGroupService->isChatAvailable($teacher)) {
                    \Log::warning('Teacher has no supervisor - chat not available', [
                        'teacher_id' => $teacher->id,
                    ]);

                    return redirect()->route('chats', ['subdomain' => $subdomain])
                        ->with('error', 'لا يمكن بدء المحادثة. لم يتم تعيين مشرف للمعلم بعد.');
                }

                $group = $chatGroupService->getOrCreateSupervisedChat($teacher, $student, $entityType, $entityId);

                if (! $group) {
                    \Log::error('Failed to create supervised chat group', [
                        'teacher_id' => $teacher->id,
                        'student_id' => $student->id,
                        'entity_type' => $entityType,
                        'entity_id' => $entityId,
                    ]);

                    return redirect()->route('chats', ['subdomain' => $subdomain])
                        ->with('error', 'حدث خطأ في إنشاء المحادثة. يرجى المحاولة لاحقاً.');
                }

                // Create or get the WireChat conversation for this group
                $conversation = null;

                if ($group->conversation_id) {
                    // Use existing conversation
                    $conversation = \Wirechat\Wirechat\Models\Conversation::find($group->conversation_id);
                }

                if (! $conversation) {
                    // Create new WireChat group conversation
                    \Illuminate\Support\Facades\DB::transaction(function () use ($group, $teacher, $student, $supervisor, $entityType, $entityId, &$conversation) {
                        // Create conversation with GROUP type
                        $conversation = new \Wirechat\Wirechat\Models\Conversation;
                        $conversation->type = \Wirechat\Wirechat\Enums\ConversationType::GROUP;
                        $conversation->save();

                        // Create the group record for WireChat
                        $conversation->group()->create([
                            'name' => $group->name,
                            'description' => 'محادثة مُشرف عليها',
                        ]);

                        // Add teacher as owner
                        \Wirechat\Wirechat\Models\Participant::create([
                            'conversation_id' => $conversation->id,
                            'participantable_id' => $teacher->id,
                            'participantable_type' => $teacher->getMorphClass(),
                            'role' => \Wirechat\Wirechat\Enums\ParticipantRole::OWNER,
                        ]);

                        // For group entities (quran_circle, interactive_course), add ALL enrolled students
                        // For individual entities, add just the single student
                        $studentsToAdd = collect();

                        if ($entityType === 'quran_circle') {
                            // QuranCircle::students() returns User models directly via many-to-many
                            $circle = \App\Models\QuranCircle::with('students')->find($entityId);
                            if ($circle) {
                                $studentsToAdd = $circle->students;
                            }
                        } elseif ($entityType === 'interactive_course') {
                            // InteractiveCourse::enrolledStudents() returns Enrollment models with student.user
                            $course = \App\Models\InteractiveCourse::with('enrolledStudents.student.user')->find($entityId);
                            if ($course) {
                                $studentsToAdd = $course->enrolledStudents->map(fn ($e) => $e->student?->user)->filter();
                            }
                        } else {
                            // Individual types - just add the single student
                            $studentsToAdd = collect([$student]);
                        }

                        // Add all students as members
                        foreach ($studentsToAdd as $studentUser) {
                            if ($studentUser && $studentUser->id !== $teacher->id) {
                                \Wirechat\Wirechat\Models\Participant::create([
                                    'conversation_id' => $conversation->id,
                                    'participantable_id' => $studentUser->id,
                                    'participantable_type' => $studentUser->getMorphClass(),
                                    'role' => \Wirechat\Wirechat\Enums\ParticipantRole::PARTICIPANT,
                                ]);
                            }
                        }

                        // Add supervisor as admin
                        if ($supervisor) {
                            \Wirechat\Wirechat\Models\Participant::create([
                                'conversation_id' => $conversation->id,
                                'participantable_id' => $supervisor->id,
                                'participantable_type' => $supervisor->getMorphClass(),
                                'role' => \Wirechat\Wirechat\Enums\ParticipantRole::ADMIN,
                            ]);
                        }

                        // Link conversation to ChatGroup
                        $group->update(['conversation_id' => $conversation->id]);
                    });
                }

                \Log::info('Supervised chat conversation ready', [
                    'group_id' => $group->id,
                    'conversation_id' => $conversation?->id,
                ]);

                // Redirect to the WireChat conversation
                if ($conversation) {
                    return redirect()->route('chat', [
                        'subdomain' => $subdomain,
                        'conversation' => $conversation->id,
                    ]);
                }

                // Fallback to chats list if something went wrong
                return redirect()->route('chats', ['subdomain' => $subdomain])
                    ->with('error', 'حدث خطأ في إنشاء المحادثة. يرجى المحاولة لاحقاً.');

            })->name('chat.start-supervised');

            Route::get('/{conversation}', \App\Livewire\Pages\Chat::class)->middleware('belongsToConversation')->name('chat');
        });
});
