<?php
declare(strict_types=1);

namespace App;


class QuarterRevenueFilter implements RevenueFilter
{
    private int $quarter;

    public function __construct(int $quarter)
    {
        $this->quarter = $quarter;
    }

    function filter(Revenue $revenue): bool
    {
        $month = intval($revenue->getDate()->format('n'));
        $quarter = ceil($month / 3);
        return $quarter == $this->quarter;
    }
}