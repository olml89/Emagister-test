<?php declare(strict_types=1);

namespace Heritages\App\Domain\Entities\Members;

use DateTimeImmutable;
use Stringable;

use Heritages\App\Domain\Exceptions\NotUniqueNameException;
use Heritages\App\Domain\Exceptions\InvalidBirthDateException;
use Heritages\App\Domain\Entities\Assets\AssetInterface;
use Heritages\App\Domain\Entities\Assets\AssetCollection;
use Heritages\App\Domain\Services\UniqueNameChecker\UniqueNameChecker;
use Heritages\App\Domain\Services\HeritageCalculator\HeritageCalculatorInterface;

final class Member implements Stringable
{
    /**
     * @var Member[]
     */
    private array $children = [];

    private AssetCollection $assets;

    private function __construct(
        private readonly string $name,
        private readonly DateTimeImmutable $birthDate,
        private readonly ?Member $parent = null,
    ) {
        $this->assets = new AssetCollection();
    }

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

        // Order the children: from older to younger
        usort(
            $this->children,
            function(Member $firstChild, Member $secondChild) : int
            {
                if ($firstChild->getBirthDate() == $secondChild->getBirthDate()) {
                    return $firstChild->getName() < $secondChild->getName() ? -1 : 1;
                }
                return $firstChild->getBirthDate() < $secondChild->getBirthDate() ? -1 : 1;
            }
        );

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

    public function hasChildren() : bool
    {
        return count($this->children) > 0;
    }

    public function isTheOldestSon() : bool
    {
        if (!$this->parent) {
            return false;
        }

        return array_search($this, $this->parent->getChildren()) === 0;
    }

    public function getAssets() : AssetCollection
    {
        return $this->assets;
    }

    public function addAsset(AssetInterface $asset) : Member
    {
        $this->assets->add($asset);
        return $this;
    }

    public function addAssets(AssetInterface ...$assets) : Member
    {
        $this->assets->addMultiple(...$assets);
        return $this;
    }

    public function getHeritage(HeritageCalculatorInterface $heritageCalculator, DateTimeImmutable $when = new DateTimeImmutable()) : int
    {
        return $heritageCalculator->getHeritage($this, $when);
    }

    public function getPatrimony(HeritageCalculatorInterface $heritageCalculator, DateTimeImmutable $when = new DateTimeImmutable()) : int
    {
        return $this->isDead($when)
            ? 0
            : $this->getHeritage($heritageCalculator, $when) + $this->assets->getValue();
    }

    public function __toString() : string
    {
        return (sprintf("%s, %s", $this->name, $this->birthDate->format("d/m/Y")));
    }

}