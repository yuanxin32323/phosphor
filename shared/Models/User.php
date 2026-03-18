<?php declare(strict_types=1);

namespace Shared\Models;

use Phosphor\Model\BaseModel;
use Phosphor\Model\Column;
use Phosphor\Model\Table;
use Shared\Enums\UserStatus;

/**
 * 用户模型
 *
 * Schema 即代码：看此文件即知 users 表的完整结构。
 */
#[Table(name: 'users')]
class User extends BaseModel
{
    #[Column(type: 'bigint', primary: true, autoIncrement: true)]
    public readonly int $id;

    #[Column(type: 'varchar', length: 100)]
    public string $name;

    #[Column(type: 'varchar', length: 255, unique: true)]
    public string $email;

    #[Column(type: 'varchar', length: 255, hidden: true)]
    public string $password;

    #[Column(type: 'enum', enumClass: UserStatus::class, default: UserStatus::Active)]
    public UserStatus $status;

    #[Column(type: 'timestamp', autoCreate: true)]
    public \DateTimeImmutable $created_at;

    #[Column(type: 'timestamp', autoUpdate: true)]
    public \DateTimeImmutable $updated_at;
}
