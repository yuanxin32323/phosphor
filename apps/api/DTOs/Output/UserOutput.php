<?php declare(strict_types=1);

namespace Apps\Api\DTOs\Output;

use Phosphor\DTO\OutputDTO;
use Phosphor\Model\BaseModel;
use Shared\Models\User;

class UserOutput extends OutputDTO
{
    public int $id;
    public string $name;
    public string $email;
    public string $status;
    public string $created_at;

    public static function from(BaseModel $model): static
    {
        /** @var User $model */
        $dto = new static();
        $dto->id = $model->id;
        $dto->name = $model->name;
        $dto->email = $model->email;
        $dto->status = $model->status->value;
        $dto->created_at = $model->created_at->format('Y-m-d H:i:s');
        return $dto;
    }
}
