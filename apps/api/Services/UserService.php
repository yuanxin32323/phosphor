<?php declare(strict_types=1);

namespace Apps\Api\Services;

use Apps\Api\DTOs\Input\CreateUserInput;
use Apps\Api\Repositories\UserRepository;
use Phosphor\Exception\NotFoundException;
use Phosphor\Log\Logger;
use Shared\Enums\UserStatus;
use Shared\Models\User;

/**
 * 用户服务
 *
 * 纯业务逻辑层，不涉及 HTTP 概念。
 */
class UserService
{
    public function __construct(
        private readonly UserRepository $userRepository,
        private readonly Logger $logger,
    ) {}

    /**
     * @return User[]
     */
    public function getAllUsers(): array
    {
        return $this->userRepository->findAll();
    }

    public function getUserById(int $id): User
    {
        $user = $this->userRepository->findById($id);
        if ($user === null) {
            throw new NotFoundException('User', $id);
        }
        return $user;
    }

    public function createUser(CreateUserInput $input): User
    {
        $user = new User();
        $user->name = $input->name;
        $user->email = $input->email;
        $user->password = password_hash($input->password, PASSWORD_ARGON2ID);
        $user->status = UserStatus::Active;

        $this->userRepository->save($user);

        $this->logger->info("User created: {$user->name}", [
            'user_id' => $user->id,
        ]);

        return $user;
    }
}
