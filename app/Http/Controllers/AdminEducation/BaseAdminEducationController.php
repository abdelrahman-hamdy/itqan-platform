<?php

namespace App\Http\Controllers\AdminEducation;

use App\Http\Controllers\Controller;
use App\Services\AcademyContextService;

/**
 * Base controller for the Admin Education management frontend.
 *
 * Provides common middleware and academy-scoping helpers
 * shared by all admin education controllers.
 */
abstract class BaseAdminEducationController extends Controller
{
    public function __construct()
    {
        $this->middleware(['auth', 'role:admin,super_admin']);
    }

    /**
     * Get the current academy ID for scoping queries.
     */
    protected function getAcademyId(): int
    {
        return AcademyContextService::getCurrentAcademy()->id;
    }
}
