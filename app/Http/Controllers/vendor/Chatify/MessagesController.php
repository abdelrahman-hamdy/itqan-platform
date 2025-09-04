<?php

namespace App\Http\Controllers\vendor\Chatify;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Response;
use Illuminate\Support\Facades\Auth;
use App\Models\User;
use App\Models\ChMessage as Message;
use App\Models\ChFavorite as Favorite;
use Chatify\Facades\ChatifyMessenger as Chatify;
use App\Models\ChatGroup;
use App\Models\ChatGroupMember;
use App\Services\ChatGroupService;
use Illuminate\Support\Facades\Request as FacadesRequest;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
class MessagesController extends Controller
{
    protected $perPage = 30;

    /**
     * Get the authenticated user's academy ID
     *
     * @return int|null
     */
    protected function getAcademyId()
    {
        return Auth::user()->academy_id;
    }

    /**
     * Check if current user can message target user based on roles
     *
     * @param User $targetUser
     * @return bool
     */
    protected function canMessage(User $targetUser)
    {
        $currentUser = Auth::user();
        
        // Super admin can message anyone
        if ($currentUser->hasRole(User::ROLE_SUPER_ADMIN)) {
            return true;
        }
        
        // Users must be in the same academy (except super admin)
        if ($currentUser->academy_id !== $targetUser->academy_id) {
            return false;
        }
        
        // Academy admin can message all users in their academy
        if ($currentUser->hasRole(User::ROLE_ACADEMY_ADMIN)) {
            return true;
        }
        
        // Supervisor can message all users in their academy
        if ($currentUser->hasRole(User::ROLE_SUPERVISOR)) {
            return true;
        }
        
        // Student permissions
        if ($currentUser->hasRole(User::ROLE_STUDENT)) {
            // Can message academy admin, supervisors
            if ($targetUser->hasRole([User::ROLE_ACADEMY_ADMIN, User::ROLE_SUPERVISOR])) {
                return true;
            }
            
            // Can message their teachers (both Quran and Academic)
            if ($targetUser->hasRole([User::ROLE_QURAN_TEACHER, User::ROLE_ACADEMIC_TEACHER])) {
                // Check if teacher teaches this student
                return $this->isTeacherOfStudent($targetUser, $currentUser);
            }
            
            // Can message their parents
            if ($targetUser->hasRole(User::ROLE_PARENT)) {
                return $this->isParentOfStudent($targetUser, $currentUser);
            }
        }
        
        // Teacher permissions (Quran or Academic)
        if ($currentUser->hasRole([User::ROLE_QURAN_TEACHER, User::ROLE_ACADEMIC_TEACHER])) {
            // Can message academy admin, supervisors
            if ($targetUser->hasRole([User::ROLE_ACADEMY_ADMIN, User::ROLE_SUPERVISOR])) {
                return true;
            }
            
            // Can message their students
            if ($targetUser->hasRole(User::ROLE_STUDENT)) {
                return $this->isTeacherOfStudent($currentUser, $targetUser);
            }
        }
        
        // Parent permissions
        if ($currentUser->hasRole(User::ROLE_PARENT)) {
            // Can message academy admin
            if ($targetUser->hasRole(User::ROLE_ACADEMY_ADMIN)) {
                return true;
            }
            
            // Can message their children
            if ($targetUser->hasRole(User::ROLE_STUDENT)) {
                return $this->isParentOfStudent($currentUser, $targetUser);
            }
            
            // Can message their children's teachers
            if ($targetUser->hasRole([User::ROLE_QURAN_TEACHER, User::ROLE_ACADEMIC_TEACHER])) {
                return $this->isTeacherOfParentChildren($targetUser, $currentUser);
            }
        }
        
        return false;
    }

    /**
     * Check if teacher teaches the student
     */
    protected function isTeacherOfStudent(User $teacher, User $student)
    {
        // Check Quran sessions - use correct column name
        $hasQuranSession = \App\Models\QuranSession::where('quran_teacher_id', $teacher->id)
            ->where('student_id', $student->id)
            ->where('academy_id', $this->getAcademyId())
            ->exists();
            
        if ($hasQuranSession) {
            return true;
        }
        
        // Check Academic sessions - use correct column name
        $hasAcademicSession = \App\Models\AcademicSession::where('academic_teacher_id', $teacher->id)
            ->where('student_id', $student->id)
            ->where('academy_id', $this->getAcademyId())
            ->exists();
            
        return $hasAcademicSession;
    }

    /**
     * Check if user is parent of student
     */
    protected function isParentOfStudent(User $parent, User $student)
    {
        return \App\Models\ParentStudent::where('parent_id', $parent->id)
            ->where('student_id', $student->id)
            ->exists();
    }

    /**
     * Check if teacher teaches any of parent's children
     */
    protected function isTeacherOfParentChildren(User $teacher, User $parent)
    {
        // Get parent's children
        $childrenIds = \App\Models\ParentStudent::where('parent_id', $parent->id)
            ->pluck('student_id');
            
        // Check if teacher teaches any of these children
        $hasQuranSession = \App\Models\QuranSession::where('teacher_id', $teacher->id)
            ->whereIn('student_id', $childrenIds)
            ->where('academy_id', $this->getAcademyId())
            ->exists();
            
        if ($hasQuranSession) {
            return true;
        }
        
        $hasAcademicSession = \App\Models\AcademicSession::where('teacher_id', $teacher->id)
            ->whereIn('student_id', $childrenIds)
            ->where('academy_id', $this->getAcademyId())
            ->exists();
            
        return $hasAcademicSession;
    }

    /**
     * Authenticate the connection for pusher
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function pusherAuth(Request $request)
    {
        $channelName = $request['channel_name'];
        $socket_id = $request['socket_id'];
        
        // Extract user ID from channel name (format: private-chatify.{userId})
        $userId = null;
        if (preg_match('/private-chatify\.(\d+)/', $channelName, $matches)) {
            $userId = $matches[1];
        }
        
        // Get the user trying to authenticate
        $requestUser = Auth::user();
        $authUser = $userId ? User::find($userId) : $requestUser;
        
        // Call pusherAuth with correct parameters
        $auth = Chatify::pusherAuth(
            $requestUser,
            $authUser, 
            $channelName,
            $socket_id
        );
        
        return Response::json($auth, 200);
    }

    /**
     * Get user's chat groups
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function getGroups(Request $request)
    {
        $user = Auth::user();
        $academyId = $this->getAcademyId();
        
        // Get all groups the user is a member of
        $groups = $user->chatGroups()
                      ->where('chat_groups.academy_id', $academyId)
                      ->where('chat_groups.is_active', true)
                      ->with(['owner', 'members'])
                      ->paginate($request->per_page ?? $this->perPage);
        
        $groupsList = [];
        foreach ($groups as $group) {
            // Get last message for the group
            $lastMessage = Message::where('group_id', $group->id)
                                 ->latest()
                                 ->first();
            
            // Get unread count for this user
            $membership = $group->memberships()
                               ->where('user_id', $user->id)
                               ->first();
            
            $groupsList[] = [
                'id' => $group->id,
                'display_name' => $group->getDisplayName(),
                'name' => $group->name,
                'avatar' => $group->avatar,
                'type' => $group->type,
                'last_message' => $lastMessage ? [
                    'body' => $lastMessage->body,
                    'from_name' => $lastMessage->from_id ? User::find($lastMessage->from_id)->getChatifyName() : 'System',
                    'created_at' => $lastMessage->created_at,
                ] : null,
                'unread_count' => $membership ? $membership->unread_count : 0,
                'members_count' => $group->members()->count(),
                'role' => $membership ? $membership->role : 'member',
            ];
        }
        
        return Response::json([
            'groups' => $groupsList,
            'total' => $groups->total(),
            'last_page' => $groups->lastPage(),
        ], 200);
    }

    /**
     * Send message to a group
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function sendToGroup(Request $request)
    {
        $error = (object)[
            'status' => 0,
            'message' => null
        ];
        $attachment = null;
        $attachment_title = null;
        
        // Check if user is a member of the group
        $group = ChatGroup::find($request['group_id']);
        if (!$group || !$group->hasMember(Auth::user())) {
            $error->status = 1;
            $error->message = 'لست عضواً في هذه المجموعة';
            return Response::json([
                'message' => $error->message,
                'error' => $error->status,
            ]);
        }
        
        // Check if user can send messages in this group
        if (!$group->canSendMessage(Auth::user())) {
            $error->status = 1;
            $error->message = 'ليس لديك صلاحية إرسال رسائل في هذه المجموعة';
            return Response::json([
                'message' => $error->message,
                'error' => $error->status,
            ]);
        }
        
        // Handle attachment if exists
        if ($request->hasFile('file')) {
            $allowed_images = Chatify::getAllowedImages();
            $allowed_files = Chatify::getAllowedFiles();
            $allowed = array_merge($allowed_images, $allowed_files);
            $file = $request->file('file');
            
            if ($file->getSize() < Chatify::getMaxUploadSize()) {
                if (in_array(strtolower($file->extension()), $allowed)) {
                    $attachment_title = $file->getClientOriginalName();
                    $attachment = Str::uuid() . "." . $file->extension();
                    $file->storeAs(config('chatify.attachments.folder'), $attachment, config('chatify.storage_disk_name'));
                } else {
                    $error->status = 1;
                    $error->message = "صيغة الملف غير مسموح بها!";
                }
            } else {
                $error->status = 1;
                $error->message = "حجم الملف كبير جداً!";
            }
        }
        
        if (!$error->status) {
            // Store the message
            $message = new Message([
                'from_id' => Auth::user()->id,
                'to_id' => null, // Group messages don't have a specific recipient
                'group_id' => $group->id,
                'academy_id' => $group->academy_id,
                'body' => htmlentities(trim($request['message']), ENT_QUOTES, 'UTF-8'),
                'attachment' => ($attachment) ? json_encode((object)[
                    'new_name' => $attachment,
                    'old_name' => $attachment_title,
                ]) : null,
                'message_type' => $attachment ? 'file' : 'text',
            ]);
            $message->save();
            
            // Increment unread count for all group members except sender
            $group->memberships()
                  ->where('user_id', '!=', Auth::user()->id)
                  ->increment('unread_count');
            
            // Broadcast to all group members
            foreach ($group->members as $member) {
                if ($member->id !== Auth::user()->id) {
                    Chatify::push("private-chatify.{$member->id}", 'messaging', [
                        'from_id' => Auth::user()->id,
                        'to_id' => $member->id,
                        'group_id' => $group->id,
                        'message' => Chatify::messageCard(
                            Chatify::fetchMessage($message->id)
                        )
                    ]);
                }
            }
        }
        
        return Response::json([
            'status' => '200',
            'error' => $error,
            'message' => Chatify::messageCard(@$message),
            'tempID' => $request['temporaryMsgId'],
        ]);
    }

    /**
     * Get group messages
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function fetchGroupMessages(Request $request)
    {
        $group = ChatGroup::find($request['group_id']);
        
        if (!$group || !$group->hasMember(Auth::user())) {
            return Response::json([
                'messages' => [],
                'error' => true,
                'message' => 'لست عضواً في هذه المجموعة'
            ]);
        }
        
        $messages = Message::where('group_id', $group->id)
                          ->with('sender')
                          ->orderBy('created_at', 'asc')
                          ->paginate($request->per_page ?? $this->perPage);
        
        $messagesList = [];
        foreach ($messages->items() as $message) {
            $fromUser = User::find($message->from_id);
            $messagesList[] = [
                'id' => $message->id,
                'from_id' => $message->from_id,
                'to_id' => $message->to_id,
                'group_id' => $message->group_id,
                'body' => $message->body,
                'attachment' => $message->attachment ? json_decode($message->attachment) : null,
                'created_at' => $message->created_at,
                'from_name' => $fromUser ? $fromUser->getChatifyName() : 'مجهول',
                'from_avatar' => $fromUser ? $fromUser->getAvatar() : null,
                'message_type' => $message->message_type ?? 'text',
            ];
        }
        
        // Mark messages as read for this user
        $membership = $group->memberships()
                           ->where('user_id', Auth::user()->id)
                           ->first();
        if ($membership) {
            $membership->markAsRead();
        }
        
        return Response::json([
            'messages' => $messagesList,
            'last_page' => $messages->lastPage(),
            'total' => $messages->total(),
        ]);
    }
    
    /**
     * Create a new chat group
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function createGroup(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'type' => 'required|string',
            'member_ids' => 'array',
            'member_ids.*' => 'exists:users,id'
        ]);
        
        $user = Auth::user();
        $academyId = $this->getAcademyId();
        
        // Create the group
        $group = ChatGroup::create([
            'academy_id' => $academyId,
            'name' => $request->name,
            'type' => $request->type,
            'owner_id' => $user->id,
            'metadata' => $request->metadata ?? [],
            'is_active' => true,
        ]);
        
        // Add creator as admin
        $service = new ChatGroupService();
        $service->addMember($group, $user, ChatGroup::ROLE_ADMIN);
        
        // Add other members
        if ($request->member_ids) {
            foreach ($request->member_ids as $memberId) {
                $member = User::find($memberId);
                if ($member && $member->academy_id == $academyId) {
                    $service->addMember($group, $member, ChatGroup::ROLE_MEMBER);
                }
            }
        }
        
        return Response::json([
            'status' => 'success',
            'message' => 'تم إنشاء المجموعة بنجاح',
            'group' => [
                'id' => $group->id,
                'name' => $group->getDisplayName(),
                'type' => $group->type,
                'member_count' => $group->members()->count(),
            ]
        ]);
    }
    
    /**
     * Add member to group
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function addGroupMember(Request $request)
    {
        $request->validate([
            'group_id' => 'required|exists:chat_groups,id',
            'user_id' => 'required|exists:users,id',
            'role' => 'string|in:member,moderator,admin'
        ]);
        
        $group = ChatGroup::find($request->group_id);
        $user = Auth::user();
        
        // Check if user can add members
        if (!$group->canManageMembers($user)) {
            return Response::json([
                'status' => 'error',
                'message' => 'ليس لديك صلاحية إضافة أعضاء'
            ], 403);
        }
        
        $newMember = User::find($request->user_id);
        if ($newMember->academy_id != $group->academy_id) {
            return Response::json([
                'status' => 'error',
                'message' => 'لا يمكن إضافة عضو من أكاديمية أخرى'
            ], 403);
        }
        
        $service = new ChatGroupService();
        $membership = $service->addMember(
            $group, 
            $newMember, 
            $request->role ?? ChatGroup::ROLE_MEMBER
        );
        
        return Response::json([
            'status' => 'success',
            'message' => 'تم إضافة العضو بنجاح',
            'member' => [
                'id' => $newMember->id,
                'name' => $newMember->getChatifyName(),
                'role' => $membership->role,
            ]
        ]);
    }
    
    /**
     * Remove member from group
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function removeGroupMember(Request $request)
    {
        $request->validate([
            'group_id' => 'required|exists:chat_groups,id',
            'user_id' => 'required|exists:users,id'
        ]);
        
        $group = ChatGroup::find($request->group_id);
        $user = Auth::user();
        
        // Check if user can remove members
        if (!$group->canManageMembers($user)) {
            return Response::json([
                'status' => 'error',
                'message' => 'ليس لديك صلاحية إزالة أعضاء'
            ], 403);
        }
        
        $memberToRemove = User::find($request->user_id);
        
        // Don't allow removing the owner
        if ($memberToRemove->id == $group->owner_id) {
            return Response::json([
                'status' => 'error',
                'message' => 'لا يمكن إزالة مالك المجموعة'
            ], 403);
        }
        
        $service = new ChatGroupService();
        $service->removeMember($group, $memberToRemove);
        
        return Response::json([
            'status' => 'success',
            'message' => 'تم إزالة العضو بنجاح'
        ]);
    }
    
    /**
     * Leave a group
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function leaveGroup(Request $request)
    {
        $request->validate([
            'group_id' => 'required|exists:chat_groups,id'
        ]);
        
        $group = ChatGroup::find($request->group_id);
        $user = Auth::user();
        
        // Check if user is a member
        if (!$group->hasMember($user)) {
            return Response::json([
                'status' => 'error',
                'message' => 'لست عضواً في هذه المجموعة'
            ], 403);
        }
        
        // Don't allow owner to leave
        if ($user->id == $group->owner_id) {
            return Response::json([
                'status' => 'error',
                'message' => 'لا يمكن لمالك المجموعة مغادرتها'
            ], 403);
        }
        
        $service = new ChatGroupService();
        $service->removeMember($group, $user);
        
        return Response::json([
            'status' => 'success',
            'message' => 'تم مغادرة المجموعة بنجاح'
        ]);
    }

    /**
     * Returning the view of the app with the required data.
     *
     * @param int $id
     * @return \Illuminate\Contracts\Foundation\Application|\Illuminate\Contracts\View\Factory|\Illuminate\Contracts\View\View
     */
    public function index( $id = null)
    {
        $user = Auth::user();
        $academy = $user->academy;
        
        // Redirect to our new unified chat system
        $subdomain = $academy->subdomain ?? 'itqan-academy';
        return redirect()->route('chat', ['subdomain' => $subdomain]);
    }

    /**
     * Get contextual contacts based on student's active subscriptions
     */
    public function getContextualContacts(Request $request)
    {
        $user = Auth::user();
        $academyId = $this->getAcademyId();
        $contacts = [];

        // Only for students
        if (!$user->hasRole(User::ROLE_STUDENT)) {
            return Response::json([
                'contacts' => [],
                'message' => 'هذه الميزة متاحة للطلاب فقط'
            ]);
        }

        // 1. Individual Quran Circle Teachers
        $individualCircles = $user->quranIndividualCircles()
            ->with(['subscription.quranTeacher.user', 'subscription.quranTeacherProfile'])
            ->whereHas('subscription', function($q) {
                $q->where('status', 'active');
            })
            ->get();

        foreach ($individualCircles as $circle) {
            if ($circle->subscription && $circle->subscription->quranTeacher) {
                $teacher = $circle->subscription->quranTeacher;
                if ($teacher && $this->canMessage($teacher)) {
                    $contacts[] = [
                        'id' => $teacher->id,
                        'name' => $teacher->getChatifyName(),
                        'email' => $teacher->email,
                        'avatar' => $teacher->getChatifyAvatar(),
                        'type' => 'individual_quran_teacher',
                        'context' => 'معلم القرآن الفردي - ' . $circle->name,
                        'activeStatus' => $teacher->active_status ?? 0,
                    ];
                }
            }
        }

        // 2. Group Quran Circle Teachers
        $groupCircles = $user->quranCircles()
            ->with(['teacher.user'])
            ->wherePivot('status', 'active')
            ->get();

        foreach ($groupCircles as $circle) {
            if ($circle->teacher && $circle->teacher->user && $this->canMessage($circle->teacher->user)) {
                $teacher = $circle->teacher->user;
                $contacts[] = [
                    'id' => $teacher->id,
                    'name' => $teacher->getChatifyName(),
                    'email' => $teacher->email,
                    'avatar' => $teacher->getChatifyAvatar(),
                    'type' => 'group_quran_teacher',
                    'context' => 'معلم حلقة القرآن - ' . $circle->name,
                    'activeStatus' => $teacher->active_status ?? 0,
                    'group_id' => $circle->id,
                ];
            }
        }

        // 3. Academic Teachers
        $academicSubscriptions = \App\Models\AcademicSubscription::where('student_id', $user->id)
            ->where('academy_id', $academyId)
            ->where('status', 'active')
            ->with(['academicTeacher.user'])
            ->get();

        foreach ($academicSubscriptions as $subscription) {
            if ($subscription->academicTeacher && $subscription->academicTeacher->user && $this->canMessage($subscription->academicTeacher->user)) {
                $teacher = $subscription->academicTeacher->user;
                $contacts[] = [
                    'id' => $teacher->id,
                    'name' => $teacher->getChatifyName(),
                    'email' => $teacher->email,
                    'avatar' => $teacher->getChatifyAvatar(),
                    'type' => 'academic_teacher',
                    'context' => 'المعلم الأكاديمي - ' . ($subscription->academicPackage->name ?? 'درس خاص'),
                    'activeStatus' => $teacher->active_status ?? 0,
                ];
            }
        }

        // 4. Interactive Course Teachers
        $courseEnrollments = $user->interactiveCourseEnrollments()
            ->where('status', 'active')
            ->with(['course.teacher.user'])
            ->get();

        foreach ($courseEnrollments as $enrollment) {
            if ($enrollment->course && $enrollment->course->teacher && $enrollment->course->teacher->user && $this->canMessage($enrollment->course->teacher->user)) {
                $teacher = $enrollment->course->teacher->user;
                $contacts[] = [
                    'id' => $teacher->id,
                    'name' => $teacher->getChatifyName(),
                    'email' => $teacher->email,
                    'avatar' => $teacher->getChatifyAvatar(),
                    'type' => 'course_teacher',
                    'context' => 'معلم الدورة - ' . $enrollment->course->title,
                    'activeStatus' => $teacher->active_status ?? 0,
                ];
            }
        }

        // 5. Academy Admin and Supervisors (always available)
        $academyStaff = User::where('academy_id', $academyId)
            ->whereIn('user_type', [User::ROLE_ACADEMY_ADMIN, User::ROLE_SUPERVISOR])
            ->where('status', 'active')
            ->get();

        foreach ($academyStaff as $staff) {
            if ($this->canMessage($staff)) {
                $contacts[] = [
                    'id' => $staff->id,
                    'name' => $staff->getChatifyName(),
                    'email' => $staff->email,
                    'avatar' => $staff->getChatifyAvatar(),
                    'type' => $staff->user_type === User::ROLE_ACADEMY_ADMIN ? 'academy_admin' : 'supervisor',
                    'context' => $staff->user_type === User::ROLE_ACADEMY_ADMIN ? 'إدارة الأكاديمية' : 'المشرف',
                    'activeStatus' => $staff->active_status ?? 0,
                ];
            }
        }

        // Remove duplicates based on user ID
        $uniqueContacts = collect($contacts)->unique('id')->values()->all();

        return Response::json([
            'contacts' => $uniqueContacts,
            'total' => count($uniqueContacts),
        ]);
    }

    /**
     * Fetch data (user, favorite.. etc).
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function idFetchData(Request $request)
    {
        try {
            $userId = $request['id'];
            $type = $request['type'] ?? 'user';
            
            if ($type === 'user') {
                $user = User::where('id', $userId)
                    ->where('academy_id', $this->getAcademyId())
                    ->first();
                
                if (!$user) {
                    return Response::json([
                        'error' => true,
                        'message' => 'المستخدم غير موجود'
                    ], 404);
                }
                
                // Check if user can message this person
                if (!$this->canMessage($user)) {
                    return Response::json([
                        'error' => true,
                        'message' => 'غير مسموح لك بمراسلة هذا المستخدم'
                    ], 403);
                }
                
                // Check if user is in favorites
                $favorite = Favorite::where('user_id', Auth::id())
                    ->where('favorite_id', $userId)
                    ->where('academy_id', $this->getAcademyId())
                    ->exists();
                
                return Response::json([
                    'error' => false,
                    'user' => [
                        'id' => $user->id,
                        'name' => $user->getChatifyName(),
                        'email' => $user->email,
                        'avatar' => $user->getChatifyAvatar(),
                        'activeStatus' => $user->active_status ?? 0,
                    ],
                    'favorite' => $favorite,
                ], 200);
            }
            
            return Response::json([
                'error' => true,
                'message' => 'نوع غير مدعوم'
            ], 400);
            
        } catch (\Exception $e) {
            \Log::error('Error in idFetchData: ' . $e->getMessage(), [
                'request' => $request->all(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return Response::json([
                'error' => true,
                'message' => 'حدث خطأ في الخادم'
            ], 500);
        }
    }

    /**
     * Fetch data (user, favorite.. etc).
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function idFetchDataOld(Request $request)
    {
        $fetch = User::where('id', $request['id'])->first();
        
        // Check if user can message this person
        if ($fetch && !$this->canMessage($fetch)) {
            return Response::json([
                'favorite' => false,
                'fetch' => null,
                'user_avatar' => null,
                'error' => 'غير مسموح لك بمراسلة هذا المستخدم',
            ]);
        }
        
        $favorite = Chatify::inFavorite($request['id']);
        $msg = Message::where('id', $request['id'])
            ->where('academy_id', $this->getAcademyId())
            ->delete();
        if ($fetch) {
            $userAvatar = Chatify::getUserWithAvatar($fetch)->avatar;
        }
        return Response::json([
            'favorite' => $favorite,
            'fetch' => $fetch ?? null,
            'user_avatar' => $userAvatar ?? null,
        ]);
    }

    /**
     * This method to make a links for the attachments
     * to be downloadable.
     *
     * @param string $fileName
     * @return \Symfony\Component\HttpFoundation\StreamedResponse|void
     */
    public function download($fileName)
    {
        $filePath = config('chatify.attachments.folder') . '/' . $fileName;
        if (Chatify::storage()->exists($filePath)) {
            return Chatify::storage()->download($filePath);
        }
        return abort(404, "Sorry, File does not exist in our server or may have been deleted!");
    }

    /**
     * Send a message to database
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function send(Request $request)
    {
        // Check if user can message the target user
        $targetUser = User::find($request['id']);
        if (!$targetUser || !$this->canMessage($targetUser)) {
            return Response::json([
                'status' => '403',
                'message' => 'غير مسموح لك بمراسلة هذا المستخدم', // Not allowed to message this user
            ], 403);
        }
        
        // default variables
        $error = (object)[
            'status' => 0,
            'message' => null
        ];
        $attachment = null;
        $attachment_title = null;

        // if there is attachment [file]
        if ($request->hasFile('file')) {
            // allowed extensions
            $allowed_images = Chatify::getAllowedImages();
            $allowed_files = Chatify::getAllowedFiles();
            $allowed = array_merge($allowed_images, $allowed_files);

            $file = $request->file('file');
            // check file size
            if ($file->getSize() < Chatify::getMaxUploadSize()) {
                if (in_array(strtolower($file->extension()), $allowed)) {
                    // get attachment name
                    $attachment_title = $file->getClientOriginalName();
                    // upload attachment and store the new name
                    $attachment = Str::uuid() . "." . $file->extension();
                    $file->storeAs(config('chatify.attachments.folder'), $attachment, config('chatify.storage_disk_name'));
                } else {
                    $error->status = 1;
                    $error->message = "File extension not allowed!";
                }
            } else {
                $error->status = 1;
                $error->message = "File size you are trying to upload is too large!";
            }
        }

        if (!$error->status) {
            $message = Chatify::newMessage([
                'from_id' => Auth::user()->id,
                'to_id' => $request['id'],
                'body' => htmlentities(trim($request['message']), ENT_QUOTES, 'UTF-8'),
                'attachment' => ($attachment) ? json_encode((object)[
                    'new_name' => $attachment,
                    'old_name' => htmlentities(trim($attachment_title), ENT_QUOTES, 'UTF-8'),
                ]) : null,
            ]);
            $message->academy_id = $this->getAcademyId();
            $message->save();
            $messageData = Chatify::parseMessage($message);
            if (Auth::user()->id != $request['id']) {
                try {
                    // Use public test channel for development/testing
                    $usePublicChannel = config('app.env') === 'local' && config('app.debug', false); // Use public channel only in local debug mode
                    
                    if ($usePublicChannel) {
                        $channelName = "public-chatify-test";
                    } else {
                        $channelName = "private-chatify." . $request['id'];
                    }
                    
                    $pushData = [
                        'from_id' => Auth::user()->id,
                        'to_id' => $request['id'],
                        'message' => Chatify::messageCard($messageData, true)
                    ];
                    
                    \Log::info('💬 Attempting to push message', [
                        'channel' => $channelName,
                        'from_id' => Auth::user()->id,
                        'to_id' => $request['id'],
                        'has_message_html' => !empty($pushData['message']),
                        'using_public_channel' => $usePublicChannel
                    ]);
                    
                    $result = Chatify::push($channelName, 'messaging', $pushData);
                    
                    \Log::info('💬 Push result', ['success' => $result ? 'true' : 'false']);
                    
                } catch (\Exception $e) {
                    \Log::error('💬 Pusher notification failed: ' . $e->getMessage(), [
                        'exception' => $e->getTraceAsString()
                    ]);
                }
            }
        }

        // send the response
        return Response::json([
            'status' => '200',
            'error' => $error,
            'message' => Chatify::messageCard(@$messageData),
            'tempID' => $request['temporaryMsgId'],
        ]);
    }

    /**
     * fetch [user/group] messages from database
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function fetch(Request $request)
    {
        $query = Chatify::fetchMessagesQuery($request['id'])
            ->where('academy_id', $this->getAcademyId())
            ->latest();
        $messages = $query->paginate($request->per_page ?? $this->perPage);
        $totalMessages = $messages->total();
        $lastPage = $messages->lastPage();
        
        $messagesList = [];
        
        if ($totalMessages > 0) {
            foreach ($messages->reverse() as $message) {
                $parsedMessage = Chatify::parseMessage($message);
                $messagesList[] = [
                    'id' => $message->id,
                    'from_id' => $message->from_id,
                    'to_id' => $message->to_id,
                    'body' => $message->body,
                    'attachment' => $message->attachment ? json_decode($message->attachment) : null,
                    'created_at' => $message->created_at,
                    'from_name' => $parsedMessage->from_name ?? '',
                    'from_avatar' => $parsedMessage->from_avatar ?? '',
                    'message_type' => $message->message_type ?? 'text',
                ];
            }
        }
        
        return Response::json([
            'total' => $totalMessages,
            'last_page' => $lastPage,
            'last_message_id' => collect($messages->items())->last()->id ?? null,
            'messages' => $messagesList,
        ]);
    }

    /**
     * Make messages as seen
     *
     * @param Request $request
     * @return JsonResponse|void
     */
    public function seen(Request $request)
    {
        // Check authentication
        if (!Auth::check()) {
            return Response::json([
                'error' => true,
                'message' => 'المستخدم غير مصادق عليه'
            ], 401);
        }

        // make as seen
        $userId = $request['id'];
        $seen = Message::where('from_id', $userId)
            ->where('to_id', Auth::user()->id)
            ->where('academy_id', $this->getAcademyId())
            ->update(['seen' => 1]);
        // send the response
        return Response::json([
            'status' => $seen,
        ], 200);
    }

    /**
     * Get contacts list
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function getContacts(Request $request)
    {
        try {
            $academyId = $this->getAcademyId();
            $currentUserId = Auth::id();
            
            // Get unique user IDs that have had conversations with current user
            $userIds = Message::where('academy_id', $academyId)
                ->where(function($q) use ($currentUserId) {
                    $q->where('from_id', $currentUserId)
                      ->orWhere('to_id', $currentUserId);
                })
                ->select('from_id', 'to_id')
                ->get()
                ->flatMap(function($message) use ($currentUserId) {
                    return [$message->from_id, $message->to_id];
                })
                ->unique()
                ->filter(function($id) use ($currentUserId) {
                    return $id != $currentUserId;
                })
                ->values();

            // Get users with their last message time
            $users = User::whereIn('id', $userIds)
                ->where('academy_id', $academyId)
                ->where('status', 'active')
                ->get();

            $contacts = [];
            foreach ($users as $user) {
                if ($this->canMessage($user)) {
                    // Get last message between users
                    $lastMessage = Message::where('academy_id', $academyId)
                        ->where(function($q) use ($user, $currentUserId) {
                            $q->where(function($subQ) use ($user, $currentUserId) {
                                $subQ->where('from_id', $currentUserId)
                                     ->where('to_id', $user->id);
                            })->orWhere(function($subQ) use ($user, $currentUserId) {
                                $subQ->where('from_id', $user->id)
                                     ->where('to_id', $currentUserId);
                            });
                        })
                        ->latest()
                        ->first();
                    
                    // Get unseen count
                    $unseenCount = Message::where('from_id', $user->id)
                        ->where('to_id', $currentUserId)
                        ->where('academy_id', $academyId)
                        ->where('seen', 0)
                        ->count();
                    
                    $contacts[] = [
                        'id' => $user->id,
                        'name' => $user->getChatifyName(),
                        'email' => $user->email,
                        'avatar' => $user->getChatifyAvatar(),
                        'user_type' => $user->user_type,
                        'activeStatus' => $user->active_status ?? 0,
                        'isOnline' => ($user->active_status ?? 0) == 1, // Convert to boolean for JavaScript
                        'lastSeen' => $user->last_seen ?? $user->updated_at,
                        'lastMessage' => $lastMessage ? [
                            'body' => $lastMessage->body,
                            'created_at' => $lastMessage->created_at,
                            'from_id' => $lastMessage->from_id,
                        ] : null,
                        'unseen' => $unseenCount,
                        'lastMessageTime' => $lastMessage ? $lastMessage->created_at : null,
                    ];
                }
            }

            // Sort by last message time
            $contacts = collect($contacts)->sortByDesc('lastMessageTime')->values()->all();

            return Response::json([
                'contacts' => $contacts,
                'total' => count($contacts),
            ], 200);
            
        } catch (\Exception $e) {
            \Log::error('Error in getContacts: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString()
            ]);
            
            return Response::json([
                'contacts' => [],
                'total' => 0,
                'error' => 'Failed to load contacts'
            ], 500);
        }
    }

    /**
     * Update user's list item data
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function updateContactItem(Request $request)
    {
        // Get user data
        $user = User::where('id', $request['user_id'])->first();
        if (!$user) {
            return Response::json([
                'message' => 'User not found!',
            ], 401);
        }
        $contactItem = Chatify::getContactItem($user);

        // send the response
        return Response::json([
            'contactItem' => $contactItem,
        ], 200);
    }

    /**
     * Put a user in the favorites list
     *
     * @param Request $request
     * @return JsonResponse|void
     */
    public function favorite(Request $request)
    {
        $userId = $request['user_id'];
        // check action [star/unstar]
        $favoriteStatus = Chatify::inFavorite($userId) ? 0 : 1;
        Chatify::makeInFavorite($userId, $favoriteStatus);

        // send the response
        return Response::json([
            'status' => @$favoriteStatus,
        ], 200);
    }

    /**
     * Get favorites list
     *
     * @param Request $request
     * @return JsonResponse|void
     */
    public function getFavorites(Request $request)
    {
        $favoritesList = null;
        $favorites = Favorite::where('user_id', Auth::user()->id)
            ->where('academy_id', $this->getAcademyId());
        foreach ($favorites->get() as $favorite) {
            // get user data
            $user = User::where('id', $favorite->favorite_id)->first();
            $favoritesList .= view('Chatify::layouts.favorite', [
                'user' => $user,
            ]);
        }
        // send the response
        return Response::json([
            'count' => $favorites->count(),
            'favorites' => $favorites->count() > 0
                ? $favoritesList
                : 0,
        ], 200);
    }

    /**
     * Search in messenger
     *
     * @param Request $request
     * @return JsonResponse|void
     */
    public function search(Request $request)
    {
        $getRecords = null;
        $input = trim(filter_var($request['input']));
        
        // Get all users in same academy (or all if super admin)
        $query = User::where('id', '!=', Auth::user()->id)
            ->where('name', 'LIKE', "%{$input}%");
            
        // Scope by academy unless super admin
        if (!Auth::user()->hasRole(User::ROLE_SUPER_ADMIN)) {
            $query->where('academy_id', $this->getAcademyId());
        }
        
        $records = $query->paginate($request->per_page ?? $this->perPage);
        
        // Filter results to only show users current user can message
        $filteredRecords = collect($records->items())->filter(function($user) {
            return $this->canMessage($user);
        });
        foreach ($filteredRecords as $record) {
            // Add Chatify info to user
            $record->chatify_info = $record->getChatifyInfo();
            $getRecords .= view('Chatify::layouts.listItem', [
                'get' => 'search_item',
                'user' => Chatify::getUserWithAvatar($record),
            ])->render();
        }
        if (count($filteredRecords) < 1) {
            $getRecords = '<p class="message-hint center-el"><span>لا توجد نتائج</span></p>';
        }
        // send the response
        return Response::json([
            'records' => $getRecords,
            'total' => count($filteredRecords),
            'last_page' => 1
        ], 200);
    }

    /**
     * Get shared photos
     *
     * @param Request $request
     * @return JsonResponse|void
     */
    public function sharedPhotos(Request $request)
    {
        // Get shared photos (with academy scoping)
        $sharedQuery = Message::where('academy_id', $this->getAcademyId())
            ->where(function ($q) use ($request) {
                $q->where('from_id', Auth::user()->id)
                    ->where('to_id', $request['user_id'])
                    ->orWhere(function ($q2) use ($request) {
                        $q2->where('from_id', $request['user_id'])
                            ->where('to_id', Auth::user()->id);
                    });
            })
            ->where('attachment', '!=', null);
        $shared = $sharedQuery->get();
        $sharedPhotos = null;

        // shared with its template
        for ($i = 0; $i < count($shared); $i++) {
            $sharedPhotos .= view('Chatify::layouts.listItem', [
                'get' => 'sharedPhoto',
                'image' => Chatify::getAttachmentUrl($shared[$i]),
            ])->render();
        }
        // send the response
        return Response::json([
            'shared' => count($shared) > 0 ? $sharedPhotos : '<p class="message-hint"><span>Nothing shared yet</span></p>',
        ], 200);
    }

    /**
     * Delete conversation
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function deleteConversation(Request $request)
    {
        // delete with academy scoping
        $delete = Message::where('academy_id', $this->getAcademyId())
            ->where(function($q) use ($request) {
                $q->where('from_id', Auth::user()->id)
                  ->where('to_id', $request['id'])
                  ->orWhere(function($q2) use ($request) {
                      $q2->where('from_id', $request['id'])
                         ->where('to_id', Auth::user()->id);
                  });
            })
            ->delete();

        // send the response
        return Response::json([
            'deleted' => $delete ? 1 : 0,
        ], 200);
    }

    /**
     * Delete message
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function deleteMessage(Request $request)
    {
        // delete
        $delete = Chatify::deleteMessage($request['id']);

        // send the response
        return Response::json([
            'deleted' => $delete ? 1 : 0,
        ], 200);
    }

    public function updateSettings(Request $request)
    {
        $msg = null;
        $error = $success = 0;

        // dark mode
        if ($request['dark_mode']) {
            $request['dark_mode'] == "dark"
                ? User::where('id', Auth::user()->id)->update(['dark_mode' => 1])  // Make Dark
                : User::where('id', Auth::user()->id)->update(['dark_mode' => 0]); // Make Light
        }

        // If messenger color selected
        if ($request['messengerColor']) {
            $messenger_color = trim(filter_var($request['messengerColor']));
            User::where('id', Auth::user()->id)
                ->update(['messenger_color' => $messenger_color]);
        }
        // if there is a [file]
        if ($request->hasFile('avatar')) {
            // allowed extensions
            $allowed_images = Chatify::getAllowedImages();

            $file = $request->file('avatar');
            // check file size
            if ($file->getSize() < Chatify::getMaxUploadSize()) {
                if (in_array(strtolower($file->extension()), $allowed_images)) {
                    // delete the older one
                    if (Auth::user()->avatar != config('chatify.user_avatar.default')) {
                        $avatar = Auth::user()->avatar;
                        if (Chatify::storage()->exists($avatar)) {
                            Chatify::storage()->delete($avatar);
                        }
                    }
                    // upload
                    $avatar = Str::uuid() . "." . $file->extension();
                    $update = User::where('id', Auth::user()->id)->update(['avatar' => $avatar]);
                    $file->storeAs(config('chatify.user_avatar.folder'), $avatar, config('chatify.storage_disk_name'));
                    $success = $update ? 1 : 0;
                } else {
                    $msg = "File extension not allowed!";
                    $error = 1;
                }
            } else {
                $msg = "File size you are trying to upload is too large!";
                $error = 1;
            }
        }

        // send the response
        return Response::json([
            'status' => $success ? 1 : 0,
            'error' => $error ? 1 : 0,
            'message' => $error ? $msg : 0,
        ], 200);
    }

    /**
     * Set user's active status
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function setActiveStatus(Request $request)
    {
        $activeStatus = $request['status'] > 0 ? 1 : 0;
        $status = User::where('id', Auth::user()->id)->update(['active_status' => $activeStatus]);
        return Response::json([
            'status' => $status,
        ], 200);
    }
}
