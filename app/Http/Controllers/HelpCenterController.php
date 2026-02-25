<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\View;

class HelpCenterController extends Controller
{
    /**
     * Map the authenticated user's role to a help config key.
     */
    private function getUserRole(): string
    {
        $user = Auth::user();

        if ($user->hasRole('super_admin')) {
            return 'developer'; // super_admin sees the developer technical docs as primary
        }

        if ($user->hasRole('admin')) {
            return 'admin';
        }

        if ($user->hasRole('quran_teacher')) {
            return 'quran_teacher';
        }

        if ($user->hasRole('academic_teacher')) {
            return 'academic_teacher';
        }

        if ($user->hasRole('supervisor')) {
            return 'supervisor';
        }

        if ($user->hasRole('parent')) {
            return 'parent';
        }

        return 'student';
    }

    /**
     * Admins/superadmins can browse any role's documentation.
     * All other roles can only view their own.
     */
    private function canAccessRole(string $role): bool
    {
        $userRole = $this->getUserRole();

        if (in_array($userRole, ['admin', 'developer'])) {
            return true; // admin and super_admin (developer) can read any role's docs
        }

        return $userRole === $role;
    }

    /**
     * Help center landing — shows the user's own role section + common articles.
     * Admins also see all other role sections.
     */
    public function index()
    {
        $userRole    = $this->getUserRole();
        $config      = config('help');
        $roleConfig  = $config['roles'][$userRole] ?? [];
        $allRoles    = $config['roles'] ?? [];
        $commonArticles = $config['common']['articles'] ?? [];

        return view('help.index', compact('userRole', 'roleConfig', 'allRoles', 'commonArticles'));
    }

    /**
     * Render a role-specific article view.
     *
     * Authorization: users can only read their own role's articles.
     * Admins/superadmins can read any role's articles.
     */
    public function article(string $role, string $slug)
    {
        if (! $this->canAccessRole($role)) {
            abort(403, __('help.article.access_denied'));
        }

        $config     = config('help');
        $roleConfig = $config['roles'][$role] ?? null;

        if (! $roleConfig || ! isset($roleConfig['articles'][$slug])) {
            abort(404);
        }

        $viewName = "help.{$role}.{$slug}";

        if (! View::exists($viewName)) {
            abort(404);
        }

        $article      = $roleConfig['articles'][$slug];
        $articleKeys  = array_keys($roleConfig['articles']);
        $currentIndex = array_search($slug, $articleKeys);

        $prevSlug    = $currentIndex > 0 ? $articleKeys[$currentIndex - 1] : null;
        $nextSlug    = $currentIndex < count($articleKeys) - 1 ? $articleKeys[$currentIndex + 1] : null;
        $prevArticle = $prevSlug ? $roleConfig['articles'][$prevSlug] : null;
        $nextArticle = $nextSlug ? $roleConfig['articles'][$nextSlug] : null;
        $userRole    = $this->getUserRole();

        return view($viewName, compact(
            'role', 'slug', 'article', 'roleConfig',
            'prevSlug', 'nextSlug', 'prevArticle', 'nextArticle',
            'userRole'
        ));
    }

    /**
     * Search page — all filtering is done client-side in Alpine.js.
     * Passes the full help config as JSON to the view.
     */
    public function search(Request $request)
    {
        $userRole    = $this->getUserRole();
        $config      = config('help');
        $canSeeAll   = in_array($userRole, ['admin', 'developer']);

        return view('help.search', compact('userRole', 'config', 'canSeeAll'));
    }

    /**
     * Render a common article (shared across all roles).
     */
    public function commonArticle(string $slug)
    {
        $config  = config('help');
        $article = $config['common']['articles'][$slug] ?? null;

        if (! $article) {
            abort(404);
        }

        $viewName = "help.common.{$slug}";

        if (! View::exists($viewName)) {
            abort(404);
        }

        $userRole = $this->getUserRole();

        return view($viewName, compact('slug', 'article', 'userRole'));
    }
}
