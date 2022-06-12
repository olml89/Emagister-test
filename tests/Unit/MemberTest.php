<?php declare(strict_types=1);

namespace Heritages\Tests\Unit;

use DateTimeImmutable;

use PHPUnit\Framework\TestCase;

use Heritages\App\Domain\Entities\Members\Member;
use Heritages\App\Domain\Exceptions\InvalidBirthDateException;

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

    public function testTheyDieAt100YearsOld() : void
    {
        $josep = Member::born('Josep', DateTimeImmutable::createFromFormat('d/m/Y H:i:s', '02/02/1920 00:00:00'));
        $certainAliveDateTime = DateTimeImmutable::createFromFormat('d/m/Y H:i:s', '01/02/2020 23:59:59');
        $certainDeadDateTime = DateTimeImmutable::createFromFormat('d/m/Y H:i:s', '02/02/2020 00:00:01');

        $this->assertFalse($josep->isDead($certainAliveDateTime));
        $this->assertTrue($josep->isDead($certainDeadDateTime));
    }

}