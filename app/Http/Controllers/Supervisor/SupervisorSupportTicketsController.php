<?php

namespace App\Http\Controllers\Supervisor;

use App\Enums\SupportTicketReason;
use App\Enums\SupportTicketStatus;
use App\Models\SupportTicket;
use App\Services\SupportTicketService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class SupervisorSupportTicketsController extends BaseSupervisorWebController
{
    public function __construct(
        private readonly SupportTicketService $service,
    ) {
        parent::__construct();
    }

    public function index(Request $request, ?string $subdomain = null): View
    {
        $scopedUserIds = $this->service->getScopedUserIdsForSupervisor(
            isAdmin: $this->isAdminUser(),
            quranTeacherIds: $this->getAssignedQuranTeacherIds(),
            academicTeacherIds: $this->getAssignedAcademicTeacherIds(),
            academicTeacherProfileIds: $this->getAssignedAcademicTeacherProfileIds(),
        );

        $filters = [
            'status' => $request->get('status'),
            'reason' => $request->get('reason'),
            'search' => $request->get('search'),
        ];

        $tickets = $this->service->getTicketsForSupervisor(
            $filters,
            $scopedUserIds,
        );

        $stats = $this->service->getTicketStats($scopedUserIds);

        // Load contact form settings for admin users
        $contactFormSettings = null;
        if ($this->isAdminUser()) {
            $contactFormSettings = $this->service->getContactFormSettings($this->getAcademyId());
        }

        return view('supervisor.support-tickets.index', [
            'tickets' => $tickets,
            'stats' => $stats,
            'filters' => $filters,
            'reasons' => SupportTicketReason::options(),
            'statuses' => SupportTicketStatus::options(),
            'isAdmin' => $this->isAdminUser(),
            'contactFormSettings' => $contactFormSettings,
        ]);
    }

    public function show(string $subdomain, SupportTicket $ticket): View
    {
        $ticket->load(['user', 'replies.user', 'closedByUser']);

        return view('supervisor.support-tickets.show', [
            'ticket' => $ticket,
            'isAdmin' => $this->isAdminUser(),
        ]);
    }

    public function reply(Request $request, string $subdomain, SupportTicket $ticket): RedirectResponse
    {
        $request->validate([
            'body' => ['required', 'string', 'min:2', 'max:2000'],
        ]);

        $this->service->addReply($ticket, Auth::user(), $request->input('body'));

        return redirect()
            ->route('manage.support-tickets.show', ['subdomain' => $subdomain, 'ticket' => $ticket])
            ->with('success', __('support.reply_sent'));
    }

    public function close(string $subdomain, SupportTicket $ticket): RedirectResponse
    {
        $this->service->closeTicket($ticket, Auth::user());

        return redirect()
            ->route('manage.support-tickets.index', ['subdomain' => $subdomain])
            ->with('success', __('support.ticket_closed'));
    }

    public function updateSettings(Request $request, string $subdomain): RedirectResponse
    {
        abort_unless($this->isAdminUser(), 403);

        $request->validate([
            'support_contact_form_enabled' => ['nullable'],
            'support_contact_form_message_ar' => ['nullable', 'string', 'max:2000'],
            'support_contact_form_message_en' => ['nullable', 'string', 'max:2000'],
        ]);

        $this->service->updateContactFormSettings($this->getAcademyId(), [
            'enabled' => $request->boolean('support_contact_form_enabled'),
            'message_ar' => $request->input('support_contact_form_message_ar', ''),
            'message_en' => $request->input('support_contact_form_message_en', ''),
        ]);

        return redirect()
            ->route('manage.support-tickets.index', ['subdomain' => $subdomain])
            ->with('success', __('support.settings_updated'));
    }
}
