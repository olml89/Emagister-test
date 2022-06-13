<?php declare(strict_types=1);

namespace Heritages\App\Domain\Entities\Members;

use DateTimeImmutable;
use Stringable;

use Heritages\App\Domain\Services\UniqueNameChecker;
use Heritages\App\Domain\Exceptions\NotUniqueNameException;
use Heritages\App\Domain\Exceptions\InvalidBirthDateException;

final class Member implements Stringable
{
    /**
     * @var Member[]
     */
    private array $children = [];

    private function __construct(
        private readonly string $name,
        private readonly DateTimeImmutable $birthDate,
        private readonly ?Member $parent = null,
    ) {}

    public static function born(string $name, DateTimeImmutable $birthDate) : Member
    {
        return new Member($name, $birthDate);
    }

    public function giveBirth(string $name, DateTimeImmutable $birthDate = new DateTimeImmutable()) : Member
    {
        $nameChecker = UniqueNameChecker::fromFamilyHead($this);

        if (!$nameChecker->checkIsUnique($name)) {
            throw new NotUniqueNameException($name);
        }

        $child = new Member($name, $birthDate, $this);

        // In PHP, a "latest" DateTime is considered "bigger".
        // So, comparing if the parent's birthDate is bigger, we are checking if he is younger.
        if ($this->birthDate > $birthDate) {
            throw new InvalidBirthDateException($this, $child);
        }

        $this->children[] = $child;
        return $child;
    }

    public function getName() : string
    {
        return $this->name;
    }

    public function getBirthDate() : DateTimeImmutable
    {
        return $this->birthDate;
    }

    public function isDead(DateTimeImmutable $when = new DateTimeImmutable()) : bool
    {
        return ($this->birthDate->diff($when)->y >= 100);
    }

    public function getParent() : ?Member
    {
        return $this->parent;
    }

    /**
     * @return Member[]
     */
    public function getChildren() : array
    {
        return $this->children;
    }

    public function __toString() : string
    {
        return (sprintf("%s, %s", $this->name, $this->birthDate->format("d/m/Y")));
    }

}