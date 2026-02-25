<?php

namespace App\Http\Middleware;

use App\Constants\DefaultAcademy;
use App\Services\AcademyContextService;
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
     * @param Closure(Request):Response $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $targetUser = $request->route('user');
        $currentUser = auth()->user();

        if (! $currentUser || ! $targetUser) {
            return $next($request);
        }

        // Cross-tenant check: verify the target user belongs to the current academy
        // This prevents accessing users from other tenants via route model binding
        $currentAcademyId = AcademyContextService::getApiContextAcademyId()
            ?? app('current_academy')?->id
            ?? $currentUser->academy_id;

        if ($currentAcademyId && ! $currentUser->isSuperAdmin() && $targetUser->academy_id !== $currentAcademyId) {
            abort(403, 'Access denied');
        }

        if (! $this->chatPermissionService->canStartPrivateChat($currentUser, $targetUser)) {
            // Get subdomain from request
            $subdomain = $request->route('subdomain') ?? DefaultAcademy::subdomain();

            return redirect()->route('chats', ['subdomain' => $subdomain])
                ->with('error', __('chat.private_not_allowed'));
        }

        return $next($request);
    }
}
