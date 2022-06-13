<?php declare(strict_types=1);

namespace Heritages\Tests\Unit;

use DateTimeImmutable;

use PHPUnit\Framework\TestCase;

use Heritages\App\Domain\Services\UniqueNameChecker\UniqueNameChecker;
use Heritages\App\Domain\Entities\Members\Member;

final class UniqueNameCheckerTest extends TestCase
{
    private Member $member;

    public function setUp(): void
    {
        parent::setUp();

        // First generation
        $grandParent = Member::born('Josep', DateTimeImmutable::createFromFormat('d/m/Y', '02/02/1920'));

        // First family nucleus
        $parent = $grandParent->giveBirth('Joan', DateTimeImmutable::createFromFormat('d/m/Y', '05/05/1950'));
        $parent->giveBirth('Jordi', DateTimeImmutable::createFromFormat('d/m/Y', '08/08/1980'));
        $parent->giveBirth('Paula', DateTimeImmutable::createFromFormat('d/m/Y', '09/09/1981'));
        $parent->giveBirth('Maria', DateTimeImmutable::createFromFormat('d/m/Y', '10/10/1982'));

        // Second family nucleus
        $firstUncle = $grandParent->giveBirth('Pere', DateTimeImmutable::createFromFormat('d/m/Y', '06/06/1952'));
        $firstUncle->giveBirth('Miquel', DateTimeImmutable::createFromFormat('d/m/Y', '03/03/1980'));
        $firstUncle->giveBirth('Ferran', DateTimeImmutable::createFromFormat('d/m/Y', '04/04/1981'));

        // Third family nucleus
        $secondUncle = $grandParent->giveBirth('Francesc', DateTimeImmutable::createFromFormat('d/m/Y', '07/07/1953'));
        $secondUncle->giveBirth('Adrià', DateTimeImmutable::createFromFormat('d/m/Y', '05/05/1980'));

        // Pointer to a family nucleus
        $this->member = $parent;
    }

    public function testDetectsRepeatedNamesInFamilyNucleus() : void
    {
        $checker = new UniqueNameChecker($this->member);

        // Repeated names in the nucleus
        $this->assertFalse($checker->checkIsUnique('Joan'));
        $this->assertFalse($checker->checkIsUnique('Jordi'));
        $this->assertFalse($checker->checkIsUnique('Paula'));
        $this->assertFalse($checker->checkIsUnique('Maria'));

        // Repeated names but on another branches of the family tree, out of the scope of the current family nucleus
        $this->assertTrue($checker->checkIsUnique('Josep'));
        $this->assertTrue($checker->checkIsUnique('Pere'));
        $this->assertTrue($checker->checkIsUnique('Miquel'));
        $this->assertTrue($checker->checkIsUnique('Ferran'));
        $this->assertTrue($checker->checkIsUnique('Francesc'));
        $this->assertTrue($checker->checkIsUnique('Adrià'));

        // Totally unused names
        $this->assertTrue($checker->checkIsUnique('Antoni'));
        $this->assertTrue($checker->checkIsUnique('Carla'));
    }

    public function testDetectsRepeatedNamesInWholeFamily() : void
    {
        $checker = UniqueNameChecker::fromFamilyHead($this->member);

        // Repeated names in the family
        $this->assertFalse($checker->checkIsUnique('Josep'));
        $this->assertFalse($checker->checkIsUnique('Joan'));
        $this->assertFalse($checker->checkIsUnique('Jordi'));
        $this->assertFalse($checker->checkIsUnique('Paula'));
        $this->assertFalse($checker->checkIsUnique('Maria'));
        $this->assertFalse($checker->checkIsUnique('Pere'));
        $this->assertFalse($checker->checkIsUnique('Miquel'));
        $this->assertFalse($checker->checkIsUnique('Ferran'));
        $this->assertFalse($checker->checkIsUnique('Francesc'));
        $this->assertFalse($checker->checkIsUnique('Adrià'));

        // Totally unused names
        $this->assertTrue($checker->checkIsUnique('Antoni'));
        $this->assertTrue($checker->checkIsUnique('Carla'));
    }

}