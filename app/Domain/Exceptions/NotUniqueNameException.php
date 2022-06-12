<?php declare(strict_types=1);

namespace Heritages\App\Domain\Exceptions;

use InvalidArgumentException;

final class NotUniqueNameException extends InvalidArgumentException
{
    public function __construct(string $name)
    {
        parent::__construct(
            sprintf(
                "The name '%s' is already in use in this family",
                $name,
            )
        );
    }
}