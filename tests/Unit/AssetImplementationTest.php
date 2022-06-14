<?php declare(strict_types=1);

namespace Heritages\tests\Unit;

use ReflectionClass;

use PHPUnit\Framework\TestCase;

use Heritages\App\Domain\Entities\Assets\Lands;
use Heritages\App\Domain\Entities\Assets\Money;
use Heritages\App\Domain\Entities\Assets\RealEstate;
use Heritages\App\Domain\Exceptions\DifferentAssetTypesException;

final class AssetImplementationTest extends TestCase
{
    private readonly int $moneyValue;
    private readonly int $squareMeterValue;
    private readonly int $propertyValue;

    public function setUp() : void
    {
        parent::setUp();
        $this->moneyValue = 1;
        $this->squareMeterValue = (new ReflectionClass(Lands::class))->getConstant('SQUARE_METER_VALUE');
        $this->propertyValue = (new ReflectionClass(RealEstate::class))->getConstant('PROPERTY_VALUE');
    }


    public function testImplementationValues() : void
    {
        // Set up
        $money = new Money(100);
        $lands = new Lands(10);
        $properties = new RealEstate(1);

        // Test
        $this->assertEquals($money->getUnits() * $this->moneyValue, $money->getValue());
        $this->assertEquals($lands->getUnits() * $this->squareMeterValue, $lands->getValue());
        $this->assertEquals($properties->getUnits() * $this->propertyValue, $properties->getValue());
    }

    public function testAddingAssets() : void
    {
        // Money
        $money = new Money(100);
        $moreMoney = new Money(50);
        $money = $money->add($moreMoney);
        $moneyValue = (100 + 50) * $this->moneyValue;
        $this->assertEquals($moneyValue, $money->getValue());

        // Lands
        $lands = new Lands(100);
        $moreLands = new Lands(200);
        $lands = $lands->add($moreLands);
        $landsValue = (100 + 200) * $this->squareMeterValue;
        $this->assertEquals($landsValue, $lands->getValue());

        // RealEstate
        $properties = new RealEstate(1);
        $anotherProperties = new RealEstate(1);
        $properties = $properties->add($anotherProperties);
        $propertiesValue = (1 + 1) * $this->propertyValue;
        $this->assertEquals($propertiesValue, $properties->getValue());

        // If we add assets of different types, there's an exception
        $this->expectException(DifferentAssetTypesException::class);
        $money->add($lands);
    }

}