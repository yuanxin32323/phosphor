<?php declare(strict_types=1);

namespace Apps\Api\Repositories;

use Phosphor\Database\BaseRepository;
use Shared\Models\User;

/**
 * @extends BaseRepository<User>
 */
class UserRepository extends BaseRepository
{
    protected function getModelClass(): string
    {
        return User::class;
    }

    /**
     * 按邮箱查找用户
     */
    public function findByEmail(string $email): ?User
    {
        $row = $this->query()->where('email', '=', $email)->first();
        if ($row === null) {
            return null;
        }
        // 使用 findById 来利用 hydrate 逻辑
        return $this->findById((int) $row['id']);
    }
}
