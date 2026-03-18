<?php declare(strict_types=1);

namespace Phosphor\Validation;

use Phosphor\DTO\InputDTO;
use Phosphor\Exception\ValidationException;
use Phosphor\Validation\Rule\Required;
use Phosphor\Validation\Rule\MaxLength;
use Phosphor\Validation\Rule\MinLength;
use Phosphor\Validation\Rule\Email;
use ReflectionClass;

/**
 * 验证引擎
 *
 * 读取 DTO 上的验证 Attribute，执行验证逻辑。
 * 验证失败时抛出 ValidationException。
 */
class Validator
{
    /**
     * 验证 InputDTO
     *
     * @throws ValidationException
     */
    public function validate(InputDTO $dto): void
    {
        $errors = [];
        $reflection = new ReflectionClass($dto);

        foreach ($reflection->getProperties() as $prop) {
            $fieldName = $prop->getName();
            $isInitialized = $prop->isInitialized($dto);
            $value = $isInitialized ? $prop->getValue($dto) : null;

            // Required 验证
            $requiredAttrs = $prop->getAttributes(Required::class);
            if ($requiredAttrs !== []) {
                /** @var Required $rule */
                $rule = $requiredAttrs[0]->newInstance();
                if (!$isInitialized || $value === null || $value === '') {
                    $errors[$fieldName][] = $rule->message;
                    continue; // 必填字段为空，跳过后续验证
                }
            }

            // 字段没有赋值，跳过其他验证
            if (!$isInitialized || $value === null) {
                continue;
            }

            // Email 验证
            $emailAttrs = $prop->getAttributes(Email::class);
            if ($emailAttrs !== [] && is_string($value)) {
                /** @var Email $rule */
                $rule = $emailAttrs[0]->newInstance();
                if (!filter_var($value, FILTER_VALIDATE_EMAIL)) {
                    $errors[$fieldName][] = $rule->message;
                }
            }

            // MaxLength 验证
            $maxLengthAttrs = $prop->getAttributes(MaxLength::class);
            if ($maxLengthAttrs !== [] && is_string($value)) {
                /** @var MaxLength $rule */
                $rule = $maxLengthAttrs[0]->newInstance();
                if (mb_strlen($value) > $rule->max) {
                    $errors[$fieldName][] = $rule->getMessage();
                }
            }

            // MinLength 验证
            $minLengthAttrs = $prop->getAttributes(MinLength::class);
            if ($minLengthAttrs !== [] && is_string($value)) {
                /** @var MinLength $rule */
                $rule = $minLengthAttrs[0]->newInstance();
                if (mb_strlen($value) < $rule->min) {
                    $errors[$fieldName][] = $rule->getMessage();
                }
            }
        }

        if ($errors !== []) {
            throw new ValidationException($errors);
        }
    }
}
