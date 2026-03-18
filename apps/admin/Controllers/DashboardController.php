<?php declare(strict_types=1);

namespace Apps\Admin\Controllers;

use Phosphor\Http\JsonResponse;
use Phosphor\Http\Method;
use Phosphor\Routing\Route;

/**
 * 管理面板控制器
 */
class DashboardController
{
    #[Route(Method::GET, '/dashboard')]
    public function index(): JsonResponse
    {
        return JsonResponse::ok([
            'app' => 'admin',
            'message' => 'Welcome to Phosphor Admin Dashboard',
        ]);
    }
}
