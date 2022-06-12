<?php declare(strict_types=1);

namespace Heritages\App\Domain\Services;

use Heritages\App\Domain\Entities\Members\Member;

final class UniqueNameChecker
{

    public function __construct(
        private Member $member,
    ) {}

    public static function fromFamilyHead(Member $member) : UniqueNameChecker
    {
        do {
            $familyHead = $member;
        } while ($member = $member->getParent());

        return new UniqueNameChecker($familyHead);
    }

    public function checkIsUnique(string $name) : bool
    {
        if ($this->member->getName() === $name) {
            return false;
        }

        foreach ($this->member->getChildren() as $child) {
            $childNameChecker = new UniqueNameChecker($child);
            if (!$childNameChecker->checkIsUnique($name)) {
                return false;
            }
        }

        return true;
    }

}