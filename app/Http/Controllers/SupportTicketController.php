<?php

namespace App\Http\Controllers;

use App\Http\Requests\ReplySupportTicketRequest;
use App\Http\Requests\StoreSupportTicketRequest;
use App\Models\SupportTicket;
use App\Services\SupportTicketService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class SupportTicketController extends Controller
{
    public function __construct(
        private readonly SupportTicketService $service,
    ) {
        $this->middleware('auth');
    }

    public function index(string $subdomain): View
    {
        $user = Auth::user();
        $tickets = $this->service->getTicketsForUser($user);
        $viewPrefix = $this->getViewPrefix($user);

        return view("{$viewPrefix}.support-tickets.index", [
            'tickets' => $tickets,
        ]);
    }

    public function create(string $subdomain): View
    {
        $user = Auth::user();
        $viewPrefix = $this->getViewPrefix($user);

        return view("{$viewPrefix}.support-tickets.create");
    }

    public function store(StoreSupportTicketRequest $request, string $subdomain): RedirectResponse
    {
        $user = Auth::user();
        $image = $request->file('image');

        $this->service->createTicket($user, $request->validated(), $image);

        $routePrefix = $user->isStudent() ? 'student.support' : 'teacher.support';

        return redirect()
            ->route("{$routePrefix}.index", ['subdomain' => $subdomain])
            ->with('success', __('support.ticket_created'));
    }

    public function show(string $subdomain, SupportTicket $ticket): View
    {
        $user = Auth::user();

        abort_unless($ticket->user_id === $user->id, 403);

        $ticket->load(['replies.user', 'closedByUser']);
        $viewPrefix = $this->getViewPrefix($user);

        return view("{$viewPrefix}.support-tickets.show", [
            'ticket' => $ticket,
        ]);
    }

    public function reply(ReplySupportTicketRequest $request, string $subdomain, SupportTicket $ticket): RedirectResponse
    {
        $user = Auth::user();

        abort_unless($ticket->user_id === $user->id, 403);

        $this->service->addReply($ticket, $user, $request->validated()['body']);

        $routePrefix = $user->isStudent() ? 'student.support' : 'teacher.support';

        return redirect()
            ->route("{$routePrefix}.show", ['subdomain' => $subdomain, 'ticket' => $ticket])
            ->with('success', __('support.reply_sent'));
    }

    private function getViewPrefix($user): string
    {
        return $user->isStudent() ? 'student' : 'teacher';
    }
}
