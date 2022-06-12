<?php declare(strict_types=1);

namespace Heritages\App\Domain\Exceptions;

use InvalidArgumentException;
use Heritages\App\Domain\Entities\Members\Member;

class InvalidBirthDateException extends InvalidArgumentException
{
    public function __construct(Member $parent, Member $child)
    {
        parent::__construct(
            sprintf(
                "A parent (%s) has to be older than his child (%s)",
                $parent,
                $child,
            )
        );
    }
}