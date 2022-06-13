<?php declare(strict_types=1);

namespace Heritages\App\Domain\Entities\Assets;

use Heritages\App\Domain\Exceptions\DifferentAssetTypesException;

abstract class AbstractAsset
{
    public function __construct(
        private int $units = 0,
    ) {}

    public function getUnits(): int
    {
        return $this->units;
    }

    public function add(AssetInterface $asset) : static
    {
        if (!($asset instanceOf static)) {
            throw new DifferentAssetTypesException($this, $asset);
        }

        $this->units += $asset->getUnits();
        return $this;
    }

}