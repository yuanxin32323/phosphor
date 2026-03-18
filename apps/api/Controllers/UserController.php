<?php declare(strict_types=1);

namespace Apps\Api\Controllers;

use Apps\Api\DTOs\Input\CreateUserInput;
use Apps\Api\DTOs\Output\UserOutput;
use Apps\Api\Services\UserService;
use Phosphor\Http\JsonResponse;
use Phosphor\Http\Method;
use Phosphor\Routing\Route;

/**
 * 用户控制器
 *
 * 路由和中间件通过 Attribute 声明，所有依赖通过构造函数注入。
 */
class UserController
{
    public function __construct(
        private readonly UserService $userService,
    ) {}

    /** 获取用户列表 */
    #[Route(Method::GET, '/users')]
    public function list(): JsonResponse
    {
        $users = $this->userService->getAllUsers();
        return JsonResponse::ok(UserOutput::fromCollection($users));
    }

    /** 创建新用户 */
    #[Route(Method::POST, '/users')]
    public function create(CreateUserInput $input): JsonResponse
    {
        $user = $this->userService->createUser($input);
        return JsonResponse::created(UserOutput::from($user));
    }

    /** 获取指定用户 */
    #[Route(Method::GET, '/users/{id}')]
    public function show(int $id): JsonResponse
    {
        $user = $this->userService->getUserById($id);
        return JsonResponse::ok(UserOutput::from($user));
    }
}
