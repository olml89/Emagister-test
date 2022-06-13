<?php declare(strict_types=1);

namespace Heritages\tests\Unit;

use PHPUnit\Framework\TestCase;

use Heritages\App\Domain\Entities\Assets\AssetCollection;
use Heritages\App\Domain\Entities\Assets\Lands;
use Heritages\App\Domain\Entities\Assets\Money;
use Heritages\App\Domain\Entities\Assets\RealEstate;
use Heritages\App\Domain\Exceptions\InvalidAssetClassException;

final class AssetCollectionTest extends TestCase
{

    public function testAddAndGetMethods() : void
    {
        // Setup
        $money = new Money(100);
        $lands = new Lands(10);
        $properties = new RealEstate(1);

        // Simple add
        $assetCollection = new AssetCollection();
        $assetCollection->add($money)->add($lands)->add($properties);
        $this->assertEquals($money->getValue(), $assetCollection->get(Money::class)->getValue());
        $this->assertEquals($lands->getValue(), $assetCollection->get(Lands::class)->getValue());
        $this->assertEquals($properties->getValue(), $assetCollection->get(RealEstate::class)->getValue());

        // Multiple add
        $assetCollection2 = new AssetCollection();
        $assetCollection2->addMultiple($money, $lands, $properties);
        $this->assertEquals($money->getValue(), $assetCollection2->get(Money::class)->getValue());
        $this->assertEquals($lands->getValue(), $assetCollection2->get(Lands::class)->getValue());
        $this->assertEquals($properties->getValue(), $assetCollection2->get(RealEstate::class)->getValue());

        // Constructor
        $assetCollection3 = new AssetCollection(...[$money, $lands, $properties]);
        $this->assertEquals($money->getValue(), $assetCollection3->get(Money::class)->getValue());
        $this->assertEquals($lands->getValue(), $assetCollection3->get(Lands::class)->getValue());
        $this->assertEquals($properties->getValue(), $assetCollection3->get(RealEstate::class)->getValue());

        // Expect and exception if we ask for objects not implementing AssetInterface
        $this->expectException(InvalidAssetClassException::class);
        $assetCollection->get('RandomFakeClass');
    }

    public function testClearMethod() : void
    {
        // Setup
        $money = new Money(100);
        $lands = new Lands(10);
        $properties = new RealEstate(1);
        $assetCollection = new AssetCollection();
        $assetCollection->add($money)->add($lands)->add($properties);

        // We see the elements
        $this->assertEquals($money->getValue(), $assetCollection->get(Money::class)->getValue());
        $this->assertEquals($lands->getValue(), $assetCollection->get(Lands::class)->getValue());
        $this->assertEquals($properties->getValue(), $assetCollection->get(RealEstate::class)->getValue());

        // Clear, we don't see anything anymore
        $assetCollection->clear();
        $this->assertEquals(0, $assetCollection->get(Money::class)->getValue());
        $this->assertEquals(0, $assetCollection->get(Lands::class)->getValue());
        $this->assertEquals(0, $assetCollection->get(RealEstate::class)->getValue());
    }

    public function testGetValueMethod() : void
    {
        // Setup
        $money = new Money(100);
        $lands = new Lands(10);
        $properties = new RealEstate(1);
        $assetCollection = new AssetCollection(...[$money, $lands, $properties]);
        $totalValue = $money->getValue() + $lands->getValue() + $properties->getValue();

        // Passing asset class names, we should get the value of the asset
        $this->assertEquals($money->getValue(), $assetCollection->getValue(Money::class));
        $this->assertEquals($lands->getValue(), $assetCollection->getValue(Lands::class));
        $this->assertEquals($properties->getValue(), $assetCollection->getValue(RealEstate::class));

        // Passing invalid asset class names, we should expect an exception
        $this->expectException(InvalidAssetClassException::class);
        $assetCollection->getValue('RandomFakeClass');

        // Passing nothing, we should get the total value of the assets
        $this->assertEquals($totalValue, $assetCollection->getValue());
    }

}