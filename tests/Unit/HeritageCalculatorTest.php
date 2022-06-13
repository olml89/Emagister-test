<?php declare(strict_types=1);

namespace Heritages\Tests\Unit;

use DateTimeImmutable;

use PHPUnit\Framework\TestCase;

use Heritages\App\Domain\Entities\Members\Member;
use Heritages\App\Domain\Entities\Assets\Money;
use Heritages\App\Domain\Entities\Assets\Lands;
use Heritages\App\Domain\Entities\Assets\RealEstate;
use Heritages\App\Domain\Services\HeritageCalculator;

final class HeritageCalculatorTest extends TestCase
{
    private readonly HeritageCalculator $heritageCalculator;

    public function setUp(): void
    {
        parent::setUp();
        $this->heritageCalculator = new HeritageCalculator();
    }

    /**
     * Un miembro A que tiene 3 hijos (B, C, D) recibe 100000 €.
     * A se quedará con 50000 €.
     * B, C, D recibirán 16667 €, 16667 € y 16666 € respectivamente.
     * Si alguno de los hijos (B, C, D) tuviera hijos, repartirá el 50% de lo recibido entre ellos, etc.
     *
     *             Ancestro misterioso
     *                 (100000)
     *                     |
     *                     A
     *                  (50000)
     *                 /   |    \
     *               /     |     \
     *             B       C      D
     *          (8334)  (16667) (16666)
     *          /    \
     *         E      F
     *      (4167)  (4166)
     *
     * (En este ejemplo se sobreentiende que A hereda de un ancestro misterioso que muere)
     */
    public function testPracticeStatement() : void
    {
        // Build the family tree
        $ancestor = Member::born('Ancestor', DateTimeImmutable::createFromFormat('d/m/Y', '02/02/1920'));
        $A = $ancestor->giveBirth('A', DateTimeImmutable::createFromFormat('d/m/Y', '05/05/1950'));
        $B = $A->giveBirth('B', DateTimeImmutable::createFromFormat('d/m/Y', '08/08/1980'));
        $E = $B->giveBirth('E', DateTimeImmutable::createFromFormat('d/m/Y', '10/10/2010'));
        $F = $B->giveBirth('F', DateTimeImmutable::createFromFormat('d/m/Y', '03/03/2012'));
        $C = $A->giveBirth('C', DateTimeImmutable::createFromFormat('d/m/Y', '06/07/1982'));
        $D = $A->giveBirth('D', DateTimeImmutable::createFromFormat('d/m/Y', '07/02/1984'));

        // Set 100000 € as heritage for the Ancestor. When he dies, the 100000 have to be passed entirely to A and then
        // be shared with the rest of descendants
        $ancestor->addAsset(new Money(100000));

        // If we check the Heritage at the current time, A is still alive but the money has to be spread along its descendants.
        $this->assertEquals(50000, $this->heritageCalculator->getHeritage($A));
        $this->assertEquals(8334, $this->heritageCalculator->getHeritage($B));
        $this->assertEquals(4167, $this->heritageCalculator->getHeritage($E));
        $this->assertEquals(4166, $this->heritageCalculator->getHeritage($F));
        $this->assertEquals(16667, $this->heritageCalculator->getHeritage($C));
        $this->assertEquals(16666, $this->heritageCalculator->getHeritage($D));

        // If we check the Heritage when A is dead, his money has to be inherited by his descendants
        $certainDeathDateForA = DateTimeImmutable::createFromFormat('d/m/Y', '07/02/2060');
        $this->assertEquals(0, $this->heritageCalculator->getHeritage($A, $certainDeathDateForA));
        $this->assertEquals(16667, $this->heritageCalculator->getHeritage($B, $certainDeathDateForA));
        $this->assertEquals(8334, $this->heritageCalculator->getHeritage($E, $certainDeathDateForA));
        $this->assertEquals(8333, $this->heritageCalculator->getHeritage($F, $certainDeathDateForA));
        $this->assertEquals(33333, $this->heritageCalculator->getHeritage($C, $certainDeathDateForA));
        $this->assertEquals(33333, $this->heritageCalculator->getHeritage($D, $certainDeathDateForA));
    }

    /*
     *                                 A
     *                   (deceased, originally: 100000)
     *                              /     \
     *                            /         \
     *                          /             \
     *                        /                 \
     *                      B                    C
     *                   (25000)              (25000)
     *                  /   |   \              /   \
     *                /     |     \           /      \
     *              D       E      F         G        H
     *            (4167)  (8333) (8333)   (12500)  (12500)
     *          /      \
     *        I         J
     *     (2084)     (2083)
     */
    public function testPracticeExample1() : void
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

        // Set 100000 € as heritage for A
        $A->addAsset(new Money(100000));

        // If we check the Heritage at the current time, A is dead and the money has to be spread along its descendants.
        $this->assertEquals(0, $this->heritageCalculator->getHeritage($A));
        $this->assertEquals(25000, $this->heritageCalculator->getHeritage($B));
        $this->assertEquals(25000, $this->heritageCalculator->getHeritage($C));
        $this->assertEquals(4167, $this->heritageCalculator->getHeritage($D));
        $this->assertEquals(8333, $this->heritageCalculator->getHeritage($E));
        $this->assertEquals(8333, $this->heritageCalculator->getHeritage($F));
        $this->assertEquals(12500, $this->heritageCalculator->getHeritage($G));
        $this->assertEquals(12500, $this->heritageCalculator->getHeritage($H));
        $this->assertEquals(2084, $this->heritageCalculator->getHeritage($I));
        $this->assertEquals(2083, $this->heritageCalculator->getHeritage($J));
    }

    public function testLandHeritage() : void
    {
        // Build the family tree
        $A = Member::born('A', DateTimeImmutable::createFromFormat('d/m/Y', '02/02/1920'));
        $B = $A->giveBirth('B', DateTimeImmutable::createFromFormat('d/m/Y', '05/05/1950'));
        $E = $B->giveBirth('E', DateTimeImmutable::createFromFormat('d/m/Y', '08/08/1980'));
        $F = $B->giveBirth('F', DateTimeImmutable::createFromFormat('d/m/Y', '02/04/1983'));
        $C = $A->giveBirth('C', DateTimeImmutable::createFromFormat('d/m/Y', '01/02/1953'));
        $D = $A->giveBirth('D', DateTimeImmutable::createFromFormat('d/m/Y', '07/02/1958'));

        // Set 1000 square meters of land for A
        $A->addAsset(new Lands(1000));

        // 1000 square meters are 300000 € worth. We want to see them inherited only by B, the older brother, but not
        // the younger brothers nor the grandsons
        $this->assertEquals(0, $this->heritageCalculator->getHeritage($A));
        $this->assertEquals((new Lands(1000))->getValue(), $this->heritageCalculator->getHeritage($B));
        $this->assertEquals(0, $this->heritageCalculator->getHeritage($C));
        $this->assertEquals(0, $this->heritageCalculator->getHeritage($D));
        $this->assertEquals(0, $this->heritageCalculator->getHeritage($E));
        $this->assertEquals(0, $this->heritageCalculator->getHeritage($F));

        // We add some lands for A
        $A->addAsset(new Lands(500));

        // We want to see them inherited only by B also
        $this->assertEquals(0, $this->heritageCalculator->getHeritage($A));
        $this->assertEquals((new Lands(1500))->getValue(), $this->heritageCalculator->getHeritage($B));
        $this->assertEquals(0, $this->heritageCalculator->getHeritage($C));
        $this->assertEquals(0, $this->heritageCalculator->getHeritage($D));
        $this->assertEquals(0, $this->heritageCalculator->getHeritage($E));
        $this->assertEquals(0, $this->heritageCalculator->getHeritage($F));

        // If we check when B is dead we want to see the Lands inherited by his older son, E, but not the youngest, F
        $certainDeadDateForB = DateTimeImmutable::createFromFormat('d/m/Y', '07/02/2060');
        $this->assertEquals(0, $this->heritageCalculator->getHeritage($B, $certainDeadDateForB));
        $this->assertEquals(0, $this->heritageCalculator->getHeritage($C, $certainDeadDateForB));
        $this->assertEquals(0, $this->heritageCalculator->getHeritage($D, $certainDeadDateForB));
        $this->assertEquals((new Lands(1500))->getValue(), $this->heritageCalculator->getHeritage($E, $certainDeadDateForB));
        $this->assertEquals(0, $this->heritageCalculator->getHeritage($F, $certainDeadDateForB));

        // If we add properties to B and check when he is dead, his older son, E, has to inherit A and B lands together
        $B->addAsset(new Lands(7000));
        $this->assertEquals(0, $this->heritageCalculator->getHeritage($B, $certainDeadDateForB));
        $this->assertEquals(0, $this->heritageCalculator->getHeritage($C, $certainDeadDateForB));
        $this->assertEquals(0, $this->heritageCalculator->getHeritage($D, $certainDeadDateForB));
        $this->assertEquals((new Lands(8500))->getValue(), $this->heritageCalculator->getHeritage($E, $certainDeadDateForB));
        $this->assertEquals(0, $this->heritageCalculator->getHeritage($F, $certainDeadDateForB));
    }

    /*
     *               A
     *               10
     *             / | \
     *            /  |   \
     *           B   C    D
     *          4    3    3
     *         [0]  [1]  [2]
     *
     *               A
     *               14
     *             / | \
     *            /  |   \
     *           B   C    D
     *          4    5    5
     *         [0]  [1]  [2]
     */
    public function testRealEstateHeritage() : void
    {
        // Build the family tree
        $A = Member::born('A', DateTimeImmutable::createFromFormat('d/m/Y', '02/02/1920'));
        $B = $A->giveBirth('B', DateTimeImmutable::createFromFormat('d/m/Y', '05/05/1950'));
        $C = $A->giveBirth('C', DateTimeImmutable::createFromFormat('d/m/Y', '01/02/1953'));
        $D = $A->giveBirth('D', DateTimeImmutable::createFromFormat('d/m/Y', '07/02/1958'));

        // Set 10 properties for A
        $realEstate = new RealEstate(10);
        $A->addAsset($realEstate);

        // A is dead.
        // We want to see 0 properties for A, 4 properties for B, 3 properties for C, 3 properties for D
        $this->assertEquals(0, $this->heritageCalculator->getHeritage($A));
        $this->assertEquals((new RealEstate(4))->getValue(), $this->heritageCalculator->getHeritage($B));
        $this->assertEquals((new RealEstate(3))->getValue(), $this->heritageCalculator->getHeritage($C));
        $this->assertEquals((new RealEstate(3))->getValue(), $this->heritageCalculator->getHeritage($D));

        // Increase to 14 properties for A, then he dies.
        // We want to see 0 properties for A, 4 properties for B, 5 properties for C, 5 properties for D
        $A->addAsset(new RealEstate(4));
        $this->assertEquals(0, $this->heritageCalculator->getHeritage($A));
        $this->assertEquals((new RealEstate(4))->getValue(), $this->heritageCalculator->getHeritage($B));
        $this->assertEquals((new RealEstate(5))->getValue(), $this->heritageCalculator->getHeritage($C));
        $this->assertEquals((new RealEstate(5))->getValue(), $this->heritageCalculator->getHeritage($D));

        // Make 3 children of B.
        $E = $B->giveBirth('E', DateTimeImmutable::createFromFormat('d/m/Y', '01/01/1980'));
        $F = $B->giveBirth('F', DateTimeImmutable::createFromFormat('d/m/Y', '02/02/1982'));
        $G = $B->giveBirth('G', DateTimeImmutable::createFromFormat('d/m/Y', '04/04/1984'));

        // If B dies we want to see 2 properties for E, 1 property for F, 1 property for G
        $certainDeadDateForB = DateTimeImmutable::createFromFormat('d/m/Y', '07/02/2060');
        $this->assertEquals(0, $this->heritageCalculator->getHeritage($B, $certainDeadDateForB));
        $this->assertEquals((new RealEstate(2))->getValue(), $this->heritageCalculator->getHeritage($E, $certainDeadDateForB));
        $this->assertEquals((new RealEstate(1))->getValue(), $this->heritageCalculator->getHeritage($F, $certainDeadDateForB));
        $this->assertEquals((new RealEstate(1))->getValue(), $this->heritageCalculator->getHeritage($G, $certainDeadDateForB));

        // Add 2 properties to B
        $B->addAsset(new RealEstate(3));

        // If B dies we want to see 2 properties for E, 3 properties for F, 3 properties for G

        /**
         *                  A
         *              (deceased)
         *              [14 props]
         *              /         \
         *            B            C
         *        [3 props]
         *        /   |   \
         *       E    F    G
         *
         * If B dies we want to see:
         * A = 0
         * B = 0
         * C = 3
         * E = 2
         * F = 2
         * G = 3
         */
        $this->assertEquals(0, $this->heritageCalculator->getHeritage($B, $certainDeadDateForB));
        $this->assertEquals((new RealEstate(2))->getValue(), $this->heritageCalculator->getHeritage($E, $certainDeadDateForB));
        $this->assertEquals((new RealEstate(2))->getValue(), $this->heritageCalculator->getHeritage($F, $certainDeadDateForB));
        $this->assertEquals((new RealEstate(3))->getValue(), $this->heritageCalculator->getHeritage($G, $certainDeadDateForB));
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
    public function testComplicatedUseCaseExtendingPracticeExample1() : void
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
        $this->assertEquals(0, $this->heritageCalculator->getHeritage($A));
        $this->assertEquals(5525000, $this->heritageCalculator->getHeritage($B));
        $this->assertEquals(3025000, $this->heritageCalculator->getHeritage($C));
        $this->assertEquals(4167, $this->heritageCalculator->getHeritage($D));
        $this->assertEquals(8333, $this->heritageCalculator->getHeritage($E));
        $this->assertEquals(8333, $this->heritageCalculator->getHeritage($F));
        $this->assertEquals(12500, $this->heritageCalculator->getHeritage($G));
        $this->assertEquals(12500, $this->heritageCalculator->getHeritage($H));
        $this->assertEquals(2084, $this->heritageCalculator->getHeritage($I));
        $this->assertEquals(2083, $this->heritageCalculator->getHeritage($J));

        /*
         * Now we want to complicate things. If B dies, its properties should be transferred to its children like this:
         * []: own
         * (): inherited from A + B (sum)
         *
         *                      B
         *                  (deceased)
         *                (50000) + [3000]
         *             (5000 sqm) + [6000 sqm]
         *               (4 prop) + [2 prop]
         *                  /    |     \
         *                /      |       \
         *              D        E        F
         *            (8334)   (16666)    (16666)
         *           (2 prop)  (2 prop)  (2 prop)
         *         (11000 sqm)
         *          /      \
         *        I         J
         *     (4167)     (4166)
         *
         * So we have:
         * A = 0
         * B = 0
         * D = (8334 + 2 * 1000000 + 11000 * 300) = 5308334
         * E = (16667 + 2 * 1000000) = 2016666
         * F = (16666 + 2 * 1000000) = 2016666
         * I = 4167
         * J = 4166
         *
         * The rest should be as in the previous test
         */
        $certainDeadDateForB = DateTimeImmutable::createFromFormat('d/m/Y', '05/05/2052');
        $this->assertEquals(0, $this->heritageCalculator->getHeritage($A, $certainDeadDateForB));
        $this->assertEquals(0, $this->heritageCalculator->getHeritage($B, $certainDeadDateForB));
        $this->assertEquals(3025000, $this->heritageCalculator->getHeritage($C, $certainDeadDateForB));
        $this->assertEquals(5308334, $this->heritageCalculator->getHeritage($D, $certainDeadDateForB));
        $this->assertEquals(2016667, $this->heritageCalculator->getHeritage($E, $certainDeadDateForB));
        $this->assertEquals(2016666, $this->heritageCalculator->getHeritage($F, $certainDeadDateForB));
        $this->assertEquals(12500, $this->heritageCalculator->getHeritage($G, $certainDeadDateForB));
        $this->assertEquals(12500, $this->heritageCalculator->getHeritage($H, $certainDeadDateForB));
        $this->assertEquals(4167, $this->heritageCalculator->getHeritage($I, $certainDeadDateForB));
        $this->assertEquals(4166, $this->heritageCalculator->getHeritage($J, $certainDeadDateForB));

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
        $this->assertEquals(0, $this->heritageCalculator->getPatrimony($A));
        $this->assertEquals(9328000, $this->heritageCalculator->getPatrimony($B));
        $this->assertEquals(65167, $this->heritageCalculator->getPatrimony($D));
        $this->assertEquals(1002084, $this->heritageCalculator->getPatrimony($I));
        $this->assertEquals(2083, $this->heritageCalculator->getPatrimony($J));
        $this->assertEquals(8333, $this->heritageCalculator->getPatrimony($E));
        $this->assertEquals(20333, $this->heritageCalculator->getPatrimony($F));
        $this->assertEquals(8035000, $this->heritageCalculator->getPatrimony($C));
        $this->assertEquals(163000, $this->heritageCalculator->getPatrimony($G));
        $this->assertEquals(5114500, $this->heritageCalculator->getPatrimony($H));
    }

}