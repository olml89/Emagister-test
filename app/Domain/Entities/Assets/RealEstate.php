<?php declare(strict_types=1);

namespace Heritages\App\Domain\Entities\Assets;

final class RealEstate extends AbstractAsset implements AssetInterface
{
    private const PROPERTY_VALUE = 1000000;

    public function getValue(): int
    {
        return $this->getUnits() * self::PROPERTY_VALUE;
    }

}