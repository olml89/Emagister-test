<?php declare(strict_types=1);

namespace Heritages\app\Domain\Services\HeritageCalculator;

use DateTimeImmutable;

use Heritages\App\Domain\Entities\Members\Member;

interface HeritageCalculatorInterface
{
    public function getHeritage(Member $member, DateTimeImmutable $when) : int;
}