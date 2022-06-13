<?php declare(strict_types=1);

namespace Heritages\App\Domain\Entities\Assets;

final class Money extends AbstractAsset implements AssetInterface
{
    public function getValue() : int
    {
        return $this->getUnits();
    }
}