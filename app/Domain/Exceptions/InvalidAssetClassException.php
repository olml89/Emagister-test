<?php declare(strict_types=1);

namespace Heritages\App\Domain\Exceptions;

use InvalidArgumentException;

use Heritages\App\Domain\Entities\Assets\AssetInterface;

final class InvalidAssetClassException extends InvalidArgumentException
{
    public function __construct(string $string)
    {
        parent::__construct(
            sprintf(
                "'%s' is not a valid class name implementing '%s'",
                $string,
                AssetInterface::class,
            )
        );
    }
}