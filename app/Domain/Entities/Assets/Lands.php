<?php declare(strict_types=1);

namespace Heritages\App\Domain\Entities\Assets;

final class Lands extends AbstractAsset implements AssetInterface
{
    private const SQUARE_METER_VALUE = 300;

    public function getValue() : int
    {
        return $this->getUnits() * self::SQUARE_METER_VALUE;
    }

}