<?php declare(strict_types=1);

namespace Heritages\App\Domain\Services\HeritageCalculator;

use DateTimeImmutable;

use Heritages\App\Domain\Entities\Members\Member;
use Heritages\App\Domain\Entities\Assets\Lands;
use Heritages\App\Domain\Entities\Assets\RealEstate;
use Heritages\App\Domain\Entities\Assets\Money;

final class HeritageCalculator implements HeritageCalculatorInterface
{

    /**
     * @param Member $member
     * @param Member[] $brothers
     * @param int $amount
     *
     * @return int
     */
    private function shareMoneyBetweenBrothers(Member $member, array $brothers, int $amount) : int
    {
        $numBrothers = count($brothers);
        $splitAmount = intdiv($amount, $numBrothers);

        /*
         * How to round up: assuming the children in the tree are always ordered from older to younger left-to-write,
         * if there is some money leftover, it is always assigned euro by euro starting from the older child.
         *
         *               50 / 3 = 16'666666, 50 % 3 = 2
         *             / | \
         *            /  |  \
         *          17  17   16
         *         [0]  [1]  [2]
         *
         *  50 % 3 = 2, [0] -> 16 + 1, [1] -> 16 + 1, [2] -> 16
         */
        if (($leftOver = $amount % $numBrothers) && array_search($member, $brothers) < $leftOver) {
            ++$splitAmount;
        }

        return $splitAmount;
    }

    /**
     * @param Member $originalDescendant
     * @param Member $member
     * @param DateTimeImmutable $when
     * @return int
     */
    private function getMoneyValueFromAncestors(Member $originalDescendant, Member $member, DateTimeImmutable $when) : int
    {
        if (!($parent = $member->getParent())) {
            return $member->getAssets()->getValue(Money::class);
        }

        $moneyAmount = $this->shareMoneyBetweenBrothers(
            $member,
            $parent->getChildren(),
            $this->getMoneyValueFromAncestors($originalDescendant, $parent, $when)
        );

        // If the member is not alive, give it all (without splitting) to the descendants
        // If the member is alive and doesn't have children, all the profits (without splitting) are for him
        if ($member->isDead($when) || !$member->hasChildren()) {
            return $moneyAmount;
        }

        // We have to spare half of the income for the children.
        // So if the end caller is the original descendant we want to round to the ceil to keep the maximum profits,
        // but if is one of the children we pass down the rounded up to the floor amount to that greedy leech.
        $moneyAmount /= 2;
        return ($member === $originalDescendant) ? (int)ceil($moneyAmount) : (int)floor($moneyAmount);
    }

    private function getLandsValueFromAncestors(Member $member, DateTimeImmutable $when) : int
    {
        if (!($parent = $member->getParent())) {
            return $member->getAssets()->getValue(Lands::class);
        }

        // If the parent is not dead, no lands to inherit
        if (!$parent->isDead($when)) {
            return 0;
        }

        $landsValue = $this->getLandsValueFromAncestors($parent, $when);

        // If the member is not alive, add their own lands to the ancestors ones and pass them down through recursion
        if ($member->isDead($when)) {
            $landsValue += $member->getAssets()->getValue(Lands::class);
        }

         // Lands are only bequeathed if the member is the oldest son
         return $member->isTheOldestSon() ? $landsValue : 0;
    }

    /**
     * @param Member $member
     * @param Member[] $brothers
     * @param RealEstate $properties
     *
     * @return RealEstate
     */
    private function sharePropertiesBetweenBrothers(Member $member, array $brothers, RealEstate $properties) : RealEstate
    {
        $numBrothers = count($brothers);
        $assignedProperties = intdiv($properties->getUnits(), $numBrothers);

        /*
         * How to share: we give a property to each brother, starting from youngest to oldest.
         * In case there are more properties we start the round again, but from the oldest to the youngest.
         * In case there are more properties we start the round again, but, like at the beginning, from youngest to oldest.
         *
         *               10
         *             / | \
         *            /  |  \
         *          4    3   3
         *         [0]  [1]  [2]
         *
         *               14
         *             / | \
         *            /  |  \
         *          4    5   5
         *         [0]  [1]  [2]
         *
         * Implementation: the assigned properties will be the number of properties of the parent between the number
         * of brothers. If there are properties left, check if the position of the member on the brothers array is smaller
         * than the leftover (assign one more property) or not. If the number of assigned properties is even, start
         * checking from the left. If it is even, start checking from the right (reverse the array).
         */
        if (($leftOver = $properties->getUnits() % $numBrothers)) {
            $brothers = ($assignedProperties % 2 === 0) ? array_reverse($brothers) : $brothers;

            if (array_search($member, $brothers) < $leftOver) {
                ++$assignedProperties;
            }
        }

        return new RealEstate($assignedProperties);
    }

    private function getRealEstateFromAncestors(Member $member, DateTimeImmutable $when) : RealEstate
    {
        if (!($parent = $member->getParent())) {
            return $member->getAssets()->get(RealEstate::class);
        }

        // If the parent is not dead, no properties to inherit
        // If the member is dead (and is the end caller), no properties to inherit
        if (!$parent->isDead($when)) {
            return new RealEstate();
        }

        $realEstate = $this->sharePropertiesBetweenBrothers(
            $member,
            $parent->getChildren(),
            $this->getRealEstateFromAncestors($parent, $when)
        );

        // If the member is not alive, add their own properties to the ancestors ones and pass them down through recursion
        if ($member->isDead($when)) {
            $realEstate->add($member->getAssets()->get(RealEstate::class));
        }

        return $realEstate;
    }

    private function getRealEstateValueFromAncestors(Member $member, DateTimeImmutable $when) : int
    {
        return $this->getRealEstateFromAncestors($member, $when)->getValue();
    }

    /**
     * This function returns only the inherited value from the ancestors
     *
     * @param Member $member
     * @param DateTimeImmutable $when
     *
     * @return int
     */
    public function getHeritage(Member $member, DateTimeImmutable $when = new DateTimeImmutable()) : int
    {
        if ($member->isDead($when)) {
            return 0;
        }

        return $this->getMoneyValueFromAncestors($member, $member, $when)
            + $this->getLandsValueFromAncestors($member, $when)
            + $this->getRealEstateValueFromAncestors($member, $when);
    }

}