<?php declare(strict_types=1);

namespace Heritages\Tests\Unit;

use DateTimeImmutable;

use PHPUnit\Framework\TestCase;

use Heritages\App\Domain\Entities\Members\Member;
use Heritages\App\Domain\Exceptions\InvalidBirthDateException;
use Heritages\App\Domain\Exceptions\NotUniqueNameException;
use Heritages\App\Domain\Entities\Assets\Lands;
use Heritages\App\Domain\Entities\Assets\Money;
use Heritages\App\Domain\Entities\Assets\RealEstate;
use Heritages\App\Domain\Services\HeritageCalculator\HeritageCalculator;

final class MemberTest extends TestCase
{

    public function testItCanBeBorn() : void
    {
        $name = 'Josep';
        $birthDate = DateTimeImmutable::createFromFormat('d/m/Y', '02/02/1920');
        $josep = Member::born($name, $birthDate);

        $this->assertEquals($name, $josep->getName());
        $this->assertEquals($birthDate, $josep->getBirthDate());
        $this->assertNull($josep->getParent());
        $this->assertEmpty($josep->getChildren());
    }

    public function testItCanGiveBirth() : void
    {
        $parent = Member::born('Josep', DateTimeImmutable::createFromFormat('d/m/Y', '02/02/1920'));
        $childName = 'Joan';
        $childBirthDate = DateTimeImmutable::createFromFormat('d/m/Y', '05/05/1950');
        $child = $parent->giveBirth($childName, $childBirthDate);

        $this->assertEquals($childName, $child->getName());
        $this->assertEquals($childBirthDate, $child->getBirthDate());
        $this->assertContainsEquals($child, $parent->getChildren());
    }

    public function testChildrenOlderThanParentNotAllowed() : void
    {
        $this->expectException(InvalidBirthDateException::class);
        $parent = Member::born('Josep', DateTimeImmutable::createFromFormat('d/m/Y', '02/02/1920'));
        $parent->giveBirth('Joan', DateTimeImmutable::createFromFormat('d/m/Y', '01/01/1900'));
    }

    public function testRepeatedNamesInTheFamilyAreNotAllowed() : void
    {
        // Set up a family tree
        $grandParent = Member::born('Josep', DateTimeImmutable::createFromFormat('d/m/Y', '02/02/1920'));
        $parent = $grandParent->giveBirth('Joan', DateTimeImmutable::createFromFormat('d/m/Y', '05/05/1950'));
        $firstSon = $parent->giveBirth('Jordi', DateTimeImmutable::createFromFormat('d/m/Y', '08/08/1980'));
        $secondSon = $parent->giveBirth('Paula', DateTimeImmutable::createFromFormat('d/m/Y', '09/09/1981'));
        $thirdSon = $parent->giveBirth('Maria', DateTimeImmutable::createFromFormat('d/m/Y', '10/10/1982'));
        $firstUncle = $grandParent->giveBirth('Pere', DateTimeImmutable::createFromFormat('d/m/Y', '06/06/1952'));
        $firstCousin = $firstUncle->giveBirth('Miquel', DateTimeImmutable::createFromFormat('d/m/Y', '03/03/1980'));
        $secondCousin = $firstUncle->giveBirth('Ferran', DateTimeImmutable::createFromFormat('d/m/Y', '04/04/1981'));
        $secondUncle = $grandParent->giveBirth('Francesc', DateTimeImmutable::createFromFormat('d/m/Y', '07/07/1953'));
        $thirdCousin = $secondUncle->giveBirth('Adrià', DateTimeImmutable::createFromFormat('d/m/Y', '05/05/1980'));

        // Allow not used names
        $firstSon->giveBirth('Antoni');
        $secondSon->giveBirth('Carla');
        $thirdSon->giveBirth('Joaquim');
        $firstCousin->giveBirth('Oriol');
        $secondCousin->giveBirth('Martí');

        // Disallow a used name
        $this->expectException(NotUniqueNameException::class);
        $thirdCousin->giveBirth('Josep');
    }

    public function testChildrenAreCorrectlyOrderedWhenGivingBirth() : void
    {
        $parent = Member::born('Josep', DateTimeImmutable::createFromFormat('d/m/Y', '02/02/1920'));

        // We make 3 children from younger to older and expect to see them ordered correctly
        $thirdSon = $parent->giveBirth('Francesc', DateTimeImmutable::createFromFormat('d/m/Y', '07/07/1953'));
        $secondSon = $parent->giveBirth('Pere', DateTimeImmutable::createFromFormat('d/m/Y', '06/06/1952'));
        $firstSon = $parent->giveBirth('Joan', DateTimeImmutable::createFromFormat('d/m/Y', '05/05/1950'));
        $this->assertEquals(0, array_search($firstSon, $parent->getChildren()));
        $this->assertEquals(1, array_search($secondSon, $parent->getChildren()));
        $this->assertEquals(2, array_search($thirdSon, $parent->getChildren()));

        // We add another child with the same birthDate as the older one, but expect to see him first because it has
        // a name that comes first in alphabetical order
        $previousSon = $parent->giveBirth('Albert', DateTimeImmutable::createFromFormat('d/m/Y', '05/05/1950'));
        $this->assertEquals(0, array_search($previousSon, $parent->getChildren()));
        $this->assertEquals(1, array_search($firstSon, $parent->getChildren()));
        $this->assertEquals(2, array_search($secondSon, $parent->getChildren()));
        $this->assertEquals(3, array_search($thirdSon, $parent->getChildren()));
    }

    public function testTheyDieAt100YearsOld() : void
    {
        $josep = Member::born('Josep', DateTimeImmutable::createFromFormat('d/m/Y H:i:s', '02/02/1920 00:00:00'));
        $certainAliveDateTime = DateTimeImmutable::createFromFormat('d/m/Y H:i:s', '01/02/2020 23:59:59');
        $certainDeadDateTime = DateTimeImmutable::createFromFormat('d/m/Y H:i:s', '02/02/2020 00:00:01');

        $this->assertFalse($josep->isDead($certainAliveDateTime));
        $this->assertTrue($josep->isDead($certainDeadDateTime));
    }

    /**
     * Between (): inherited when A dies
     * Between []: already theirs
     *
     *                                 A
     *                   (deceased, originally: 100000)
     *                   (deceased, originally: 7 properties)
     *                   (deceased, originally: 5000 square meters of land)
     *                              /     \
     *                            /         \
     *                          /             \
     *                        /                 \
     *                      B                    C
     *                   (25000)              (25000)
     *                    [3000]              [10000]
     *                  [6000 sqm]            [0 sqm]
     *                [2 properties]        [5 properties]
     *                  /   |   \              /   \
     *                /     |     \           /      \
     *              D       E      F         G        H
     *            (4167)  (8333) (8333)   (12500)  (12500)
     *           [1000]          [12000]   [500]     [2000]
     *          [200 sqm]                 [500 sqm] [7000 sqm]
     *        [0 properties]                       [3 properties]
     *          /      \
     *        I         J
     *     (2084)     (2083)
     *  [1 property]
     */
    public function testIntegrationWithHeritageCalculator() : void
    {
        // Build the family tree
        $A = Member::born('A', DateTimeImmutable::createFromFormat('d/m/Y', '02/02/1920'));
        $B = $A->giveBirth('B', DateTimeImmutable::createFromFormat('d/m/Y', '05/05/1950'));
        $D = $B->giveBirth('D', DateTimeImmutable::createFromFormat('d/m/Y', '08/08/1980'));
        $I = $D->giveBirth('I', DateTimeImmutable::createFromFormat('d/m/Y', '10/10/2010'));
        $J = $D->giveBirth('J', DateTimeImmutable::createFromFormat('d/m/Y', '03/03/2012'));
        $E = $B->giveBirth('E', DateTimeImmutable::createFromFormat('d/m/Y', '06/07/1982'));
        $F = $B->giveBirth('F', DateTimeImmutable::createFromFormat('d/m/Y', '07/02/1984'));
        $C = $A->giveBirth('C', DateTimeImmutable::createFromFormat('d/m/Y', '01/02/1953'));
        $G = $C->giveBirth('G', DateTimeImmutable::createFromFormat('d/m/Y', '11/03/1985'));
        $H = $C->giveBirth('H', DateTimeImmutable::createFromFormat('d/m/Y', '04/09/1986'));

        // Inject assets
        $A->addAssets(new Money(100000), new RealEstate(7), new Lands(5000));
        $B->addAssets(new Money(3000), new RealEstate(2), new Lands(6000));
        $D->addAssets(new Money(1000), new Lands(200));
        $I->addAssets(new RealEstate(1));
        $F->addAssets(new Money(12000));
        $C->addAssets(new Money(10000), new RealEstate(5));
        $G->addAssets(new Money(500), new Lands(500));
        $H->addAssets(new Money(2000), new RealEstate(3), new Lands(7000));

        // Create the heritage calculator
        $heritageCalculator = new HeritageCalculator();

        /*
         * If A dies, we expect the money to be shared through all the descendants as before, but the lands
         * and the properties between its children. So we expect to end up with:
         *
         * A = 0
         * B = (25000 + 4 properties + 5000 square meters) = (25000 + 4 * 1000000 + 5000 * 300) = 5525000
         * C = (25000 + 3 properties) = (25000 + 3 * 1000000) = 3025000
         *
         * The rest should be as in the Example 1
         */
        $this->assertEquals(0, $A->getHeritage($heritageCalculator));
        $this->assertEquals(5525000, $B->getHeritage($heritageCalculator));
        $this->assertEquals(3025000, $C->getHeritage($heritageCalculator));
        $this->assertEquals(4167, $D->getHeritage($heritageCalculator));
        $this->assertEquals(8333, $E->getHeritage($heritageCalculator));
        $this->assertEquals(8333, $F->getHeritage($heritageCalculator));
        $this->assertEquals(12500, $G->getHeritage($heritageCalculator));
        $this->assertEquals(12500, $H->getHeritage($heritageCalculator));
        $this->assertEquals(2084, $I->getHeritage($heritageCalculator));
        $this->assertEquals(2083, $J->getHeritage($heritageCalculator));

        /*
         * This is even more complicated because we not only get the heritage, but the patrimony: we calculate
         * the amount of wealth for each family member, including heritage (inherited goods) and its own assets.
         *
         * We expect:
         * A = 0
         * B = (3000 + 2 * 1000000 + 6000 * 300) + (25000 + 4 * 1000000 + 5000 * 300) = 9328000
         * D = (1000 + 200 * 300) + (4167) = 65167
         * I = (1 * 1000000) + (2084) = 1002084
         * J = 2083
         * E = 8333
         * F = (12000) + (8333) = 20333
         * C = (10000 + 5 * 1000000) + (25000 + 3 * 1000000) = 8035000
         * G = (500 + 500 * 300) + (12500) = 163000
         * H = (2000 + 7000 * 300 + 3 * 1000000) + (12500) = 5114500
         */
        $this->assertEquals(0, $A->getPatrimony($heritageCalculator));
        $this->assertEquals(9328000, $B->getPatrimony($heritageCalculator));
        $this->assertEquals(65167, $D->getPatrimony($heritageCalculator));
        $this->assertEquals(1002084, $I->getPatrimony($heritageCalculator));
        $this->assertEquals(2083, $J->getPatrimony($heritageCalculator));
        $this->assertEquals(8333, $E->getPatrimony($heritageCalculator));
        $this->assertEquals(20333, $F->getPatrimony($heritageCalculator));
        $this->assertEquals(8035000, $C->getPatrimony($heritageCalculator));
        $this->assertEquals(163000, $G->getPatrimony($heritageCalculator));
        $this->assertEquals(5114500, $H->getPatrimony($heritageCalculator));
    }

}