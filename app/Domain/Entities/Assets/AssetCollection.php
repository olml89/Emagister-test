<?php declare(strict_types=1);

namespace Heritages\App\Domain\Entities\Assets;

use Heritages\App\Domain\Exceptions\InvalidAssetClassException;

final class AssetCollection
{
    /**
     * @var array<class-string<AssetInterface>, AssetInterface>
     */
    private array $assets = [];

    public function __construct(AssetInterface ...$assets)
    {
        $this->addMultiple(...$assets);
    }

    public function add(AssetInterface $asset) : AssetCollection
    {
        $this->assets[$asset::class] = array_key_exists($asset::class, $this->assets)
            ? $this->assets[$asset::class]->add($asset)
            : $asset;

        return $this;
    }

    public function addMultiple(AssetInterface ...$assets) : AssetCollection
    {
        foreach($assets as $asset) {
            $this->add($asset);
        }

        return $this;
    }

    public function clear() : AssetCollection
    {
        $this->assets = [];
        return $this;
    }

    /**
     * @param class-string<AssetInterface> $assetType
     * @return AssetInterface
     */
    public function get(string $assetType) : AssetInterface
    {
        if (!is_subclass_of($assetType, AssetInterface::class)) {
            throw new InvalidAssetClassException($assetType);
        }

        return $this->assets[$assetType] ?? new $assetType();
    }

    /**
     * @param class-string<AssetInterface>|null $assetType
     * @return int
     */
    public function getValue(?string $assetType = null) : int
    {
        if (!is_null($assetType)) {
            return $this->get($assetType)?->getValue();
        }

        return array_reduce(
            $this->assets,
            function (int $carry, AssetInterface $asset) : int
            {
                return $carry + $asset->getValue();
            },
            initial: 0
        );
    }

}