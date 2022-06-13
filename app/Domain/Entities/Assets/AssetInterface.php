<?php declare(strict_types=1);

namespace Heritages\App\Domain\Entities\Assets;

interface AssetInterface
{
    public function getUnits() : int;
    public function getValue() : int;
    public function add(AssetInterface $asset) : static;
}