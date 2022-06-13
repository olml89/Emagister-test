<?php declare(strict_types=1);

namespace Heritages\App\Domain\Exceptions;

use InvalidArgumentException;

use Heritages\App\Domain\Entities\Assets\AssetInterface;

final class DifferentAssetTypesException extends InvalidArgumentException
{
    public function __construct(AssetInterface $expected, AssetInterface $given)
    {
        parent::__construct(
            sprintf("Expected asset of class '%s', asset of class '%s' given", $expected::class, $given::class)
        );
    }
}