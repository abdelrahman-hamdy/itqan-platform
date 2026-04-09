<?php

namespace App\Services;

use App\Enums\NotificationCategory;
use App\Enums\NotificationType;
use App\Enums\SupportTicketStatus;
use App\Enums\UserType;
use App\Models\AcademicSubscription;
use App\Models\AcademySettings;
use App\Models\QuranSubscription;
use App\Models\SupportTicket;
use App\Models\SupportTicketReply;
use App\Models\User;
use App\Services\Notification\NotificationRepository;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\UploadedFile;

class SupportTicketService
{
    public function __construct(
        private readonly NotificationRepository $notificationRepository,
    ) {}

    /**
     * Create a new support ticket.
     */
    public function createTicket(User $user, array $data, ?UploadedFile $image = null): SupportTicket
    {
        $imagePath = null;
        if ($image) {
            $directory = "tenants/{$user->academy_id}/support-tickets";
            $imagePath = $image->store($directory, 'public');
        }

        $ticket = SupportTicket::query()->create([
            'academy_id' => $user->academy_id,
            'user_id' => $user->id,
            'reason' => $data['reason'],
            'description' => $data['description'],
            'image_path' => $imagePath,
            'status' => SupportTicketStatus::OPEN,
        ]);

        $ticket->load('user');

        // Notify all supervisors/admins about the new ticket
        $this->getAcademySupervisors($ticket->academy_id)->each(function (User $supervisor) use ($ticket) {
            $this->sendNotification(
                user: $supervisor,
                type: NotificationType::SUPPORT_TICKET_CREATED,
                title: __('support.notifications.new_ticket_title'),
                message: __('support.notifications.new_ticket_message', [
                    'name' => $ticket->user->name,
                    'reason' => $ticket->reason->label(),
                ]),
                actionUrl: $this->getTicketUrl($ticket, $supervisor),
                ticket: $ticket,
            );
        });

        return $ticket;
    }

    /**
     * Add a reply to a ticket.
     */
    public function addReply(SupportTicket $ticket, User $user, string $body): SupportTicketReply
    {
        $ticket->loadMissing('user');

        $reply = $ticket->replies()->create([
            'user_id' => $user->id,
            'body' => $body,
        ]);

        if ($user->isAdmin() || $user->isSupervisor()) {
            // Admin/supervisor replied — notify the ticket owner
            $this->sendNotification(
                user: $ticket->user,
                type: NotificationType::SUPPORT_TICKET_REPLIED,
                title: __('support.notifications.reply_title'),
                message: __('support.notifications.reply_message', ['name' => $user->name]),
                actionUrl: $this->getTicketUrl($ticket, $ticket->user),
                ticket: $ticket,
            );
        } else {
            // Reporter replied — notify supervisors
            $this->getAcademySupervisors($ticket->academy_id)->each(function (User $supervisor) use ($ticket, $user) {
                $this->sendNotification(
                    user: $supervisor,
                    type: NotificationType::SUPPORT_TICKET_REPLIED,
                    title: __('support.notifications.reply_title'),
                    message: __('support.notifications.reply_message', ['name' => $user->name]),
                    actionUrl: $this->getTicketUrl($ticket, $supervisor),
                    ticket: $ticket,
                );
            });
        }

        return $reply;
    }

    /**
     * Close a ticket.
     */
    public function closeTicket(SupportTicket $ticket, User $closedBy): void
    {
        $ticket->update([
            'status' => SupportTicketStatus::CLOSED,
            'closed_at' => now(),
            'closed_by' => $closedBy->id,
        ]);
    }

    /**
     * Get tickets for a specific user (their own tickets).
     */
    public function getTicketsForUser(User $user, bool $includeClosedTickets = false): LengthAwarePaginator
    {
        $query = SupportTicket::query()
            ->forUser($user->id)
            ->with(['replies.user'])
            ->withCount('replies')
            ->latest();

        if (! $includeClosedTickets) {
            $query->open();
        }

        return $query->paginate(15);
    }

    /**
     * Get tickets for supervisor/admin view with scoping and filters.
     */
    public function getTicketsForSupervisor(
        array $filters = [],
        ?array $scopedUserIds = null,
    ): LengthAwarePaginator {
        $query = SupportTicket::query()
            ->with(['user', 'closedByUser'])
            ->withCount('replies');

        if ($scopedUserIds !== null) {
            $query->whereIn('user_id', $scopedUserIds);
        }

        if (! empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (! empty($filters['reason'])) {
            $query->where('reason', $filters['reason']);
        }

        if (! empty($filters['search'])) {
            $query->where(function (Builder $q) use ($filters) {
                $q->where('description', 'like', '%'.$filters['search'].'%')
                    ->orWhereHas('user', function (Builder $userQuery) use ($filters) {
                        $userQuery->where('name', 'like', '%'.$filters['search'].'%');
                    });
            });
        }

        return $query->latest()->paginate(20);
    }

    /**
     * Get user IDs within a supervisor's scope (their assigned teachers + those teachers' students).
     *
     * @return int[]|null  null means admin (no scoping needed)
     */
    public function getScopedUserIdsForSupervisor(
        bool $isAdmin,
        array $quranTeacherIds,
        array $academicTeacherIds,
        array $academicTeacherProfileIds,
    ): ?array {
        if ($isAdmin) {
            return null;
        }

        $teacherUserIds = array_merge($quranTeacherIds, $academicTeacherIds);

        $studentIds = collect();

        if (! empty($quranTeacherIds)) {
            $studentIds = $studentIds->merge(
                QuranSubscription::query()
                    ->whereIn('quran_teacher_id', $quranTeacherIds)
                    ->pluck('student_id')
            );
        }

        if (! empty($academicTeacherProfileIds)) {
            $studentIds = $studentIds->merge(
                AcademicSubscription::query()
                    ->whereIn('teacher_id', $academicTeacherProfileIds)
                    ->pluck('student_id')
            );
        }

        return array_values(array_unique(array_merge($teacherUserIds, $studentIds->toArray())));
    }

    /**
     * Get ticket stats for supervisor dashboard in a single query.
     *
     * @return array{open: int, closed: int, total: int}
     */
    public function getTicketStats(?array $scopedUserIds = null): array
    {
        $stats = SupportTicket::query()
            ->when($scopedUserIds !== null, fn (Builder $q) => $q->whereIn('user_id', $scopedUserIds))
            ->selectRaw('COUNT(*) as total, SUM(CASE WHEN status = ? THEN 1 ELSE 0 END) as open_count', [SupportTicketStatus::OPEN->value])
            ->first();

        return [
            'open' => (int) $stats->open_count,
            'closed' => (int) $stats->total - (int) $stats->open_count,
            'total' => (int) $stats->total,
        ];
    }

    /**
     * Get contact form settings for an academy.
     */
    public function getContactFormSettings(int $academyId): array
    {
        $settings = AcademySettings::query()
            ->where('academy_id', $academyId)
            ->first();

        return [
            'enabled' => $settings?->getSetting('support_contact_form_enabled', false),
            'message_ar' => $settings?->getSetting('support_contact_form_message_ar', ''),
            'message_en' => $settings?->getSetting('support_contact_form_message_en', ''),
        ];
    }

    /**
     * Update contact form settings.
     */
    public function updateContactFormSettings(int $academyId, array $data): void
    {
        $settings = AcademySettings::query()->firstOrCreate(
            ['academy_id' => $academyId],
            []
        );

        if (array_key_exists('enabled', $data)) {
            $settings->setSetting('support_contact_form_enabled', (bool) $data['enabled']);
        }

        if (array_key_exists('message_ar', $data)) {
            $settings->setSetting('support_contact_form_message_ar', $data['message_ar']);
        }

        if (array_key_exists('message_en', $data)) {
            $settings->setSetting('support_contact_form_message_en', $data['message_en']);
        }

        cache()->forget("support_form_settings:{$academyId}");
    }

    // ========================================
    // Private Helpers
    // ========================================

    private function getAcademySupervisors(int $academyId): Collection
    {
        return User::query()
            ->where('academy_id', $academyId)
            ->whereIn('user_type', [
                UserType::SUPERVISOR->value,
                UserType::SUPER_ADMIN->value,
                UserType::ADMIN->value,
            ])
            ->get();
    }

    /**
     * Send a notification via the NotificationRepository (sets action_url column properly).
     */
    private function sendNotification(
        User $user,
        NotificationType $type,
        string $title,
        string $message,
        string $actionUrl,
        SupportTicket $ticket,
    ): void {
        $category = NotificationCategory::SYSTEM;

        $this->notificationRepository->create($user, [
            'type' => $type->value,
            'notification_type' => $type->value,
            'category' => $category->value,
            'icon' => $category->getIcon(),
            'icon_color' => $category->getColor(),
            'action_url' => $actionUrl,
            'is_important' => false,
            'tenant_id' => $user->academy_id,
            'metadata' => ['ticket_id' => $ticket->id],
            'data' => [
                'title' => $title,
                'message' => $message,
                'body' => $message,
                'category' => $category->value,
                'icon' => $category->getIcon(),
                'color' => $category->getColor(),
                'action_url' => $actionUrl,
                'iconColor' => $category->getFilamentColor(),
                'format' => 'filament',
                'duration' => 'persistent',
            ],
        ]);
    }

    /**
     * Generate the ticket URL based on the recipient's role.
     */
    private function getTicketUrl(SupportTicket $ticket, User $recipient): string
    {
        $subdomain = $recipient->academy->subdomain ?? 'itqan-academy';

        if ($recipient->isStudent()) {
            return route('student.support.show', ['subdomain' => $subdomain, 'ticket' => $ticket->id]);
        }

        if ($recipient->isQuranTeacher() || $recipient->isAcademicTeacher()) {
            return route('teacher.support.show', ['subdomain' => $subdomain, 'ticket' => $ticket->id]);
        }

        // Supervisor/admin → supervisor panel
        return route('manage.support-tickets.show', ['subdomain' => $subdomain, 'ticket' => $ticket->id]);
    }
}
