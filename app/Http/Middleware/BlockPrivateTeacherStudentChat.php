<?php

namespace App\Http\Middleware;

use App\Services\ChatPermissionService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class BlockPrivateTeacherStudentChat
{
    protected ChatPermissionService $chatPermissionService;

    public function __construct(ChatPermissionService $chatPermissionService)
    {
        $this->chatPermissionService = $chatPermissionService;
    }

    /**
     * Handle an incoming request.
     *
     * Blocks private chat attempts between teachers and students.
     * They must use supervised group chats instead.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $targetUser = $request->route('user');
        $currentUser = auth()->user();

        if (! $currentUser || ! $targetUser) {
            return $next($request);
        }

        if (! $this->chatPermissionService->canStartPrivateChat($currentUser, $targetUser)) {
            // Get subdomain from request
            $subdomain = $request->route('subdomain') ?? 'itqan-academy';

            return redirect()->route('chats', ['subdomain' => $subdomain])
                ->with('error', __('chat.private_not_allowed'));
        }

        return $next($request);
    }
}
