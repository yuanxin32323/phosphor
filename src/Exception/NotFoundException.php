<?php declare(strict_types=1);

namespace Phosphor\Exception;

class NotFoundException extends HttpException
{
    public function __construct(string $resource, int|string $id)
    {
        parent::__construct("{$resource} (ID: {$id}) 不存在", 404);
    }
}
