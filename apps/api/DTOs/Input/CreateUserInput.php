<?php declare(strict_types=1);

namespace Apps\Api\DTOs\Input;

use Phosphor\DTO\InputDTO;
use Phosphor\Validation\Rule;

class CreateUserInput extends InputDTO
{
    #[Rule\Required]
    #[Rule\MaxLength(100)]
    public string $name;

    #[Rule\Required]
    #[Rule\Email]
    public string $email;

    #[Rule\Required]
    #[Rule\MinLength(8)]
    public string $password;
}
