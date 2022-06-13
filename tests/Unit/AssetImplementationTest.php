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
    private int $moneyValue = 1;
    private int $squareMeterValue;
    private int $propertyValue;

    public function setUp() : void
    {
        parent::setUp();
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
        $combinedMoney = $money->add($moreMoney);
        $combinedMoneyValue = (100 + 50) * $this->moneyValue;
        $this->assertEquals($combinedMoneyValue, $combinedMoney->getValue());

        // Lands
        $lands = new Lands(100);
        $moreLands = new Lands(200);
        $combinedLands = $lands->add($moreLands);
        $combinedLandsValue = (100 + 200) * $this->squareMeterValue;
        $this->assertEquals($combinedLandsValue, $combinedLands->getValue());

        // RealEstate
        $property = new RealEstate(1);
        $anotherProperty = new RealEstate(1);
        $combinedProperties = $property->add($anotherProperty);
        $combinedPropertiesValue = (1 + 1) * $this->propertyValue;
        $this->assertEquals($combinedPropertiesValue, $combinedProperties->getValue());

        // If we add assets of different types, there's an exception
        $this->expectException(DifferentAssetTypesException::class);
        $money->add($lands);
    }
    
}