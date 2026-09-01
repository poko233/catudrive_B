<?php

declare(strict_types=1);

namespace App\Modules\Auth\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Auth\Services\PermissionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SidebarController extends Controller
{
    public function __construct(
        private readonly PermissionService $permissionService
    ) {
    }

    /**
     * GET /api/sidebar
     */
    public function index(Request $request): JsonResponse
    {
        return response()->json([
            'data' => $this->permissionService->getSidebar(
                $request->user()
            ),
        ]);
    }
}
