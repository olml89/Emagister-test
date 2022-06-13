<?php declare(strict_types=1);

namespace Heritages\Tests\Unit;

use DateTimeImmutable;

use PHPUnit\Framework\TestCase;

use Heritages\App\Domain\Entities\Members\Member;
use Heritages\App\Domain\Exceptions\InvalidBirthDateException;
use Heritages\App\Domain\Exceptions\NotUniqueNameException;

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

}