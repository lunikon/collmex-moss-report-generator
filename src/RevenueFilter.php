<?php
declare(strict_types=1);

namespace App;

interface RevenueFilter
{
    function filter(Revenue $revenue): bool;
}