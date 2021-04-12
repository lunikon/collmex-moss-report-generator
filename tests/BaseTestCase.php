<?php
declare(strict_types=1);

namespace App;

use Brick\Math\BigDecimal;
use DateTimeImmutable;
use PHPUnit\Framework\TestCase;

abstract class BaseTestCase extends TestCase
{
    protected function taxRates(): TaxRates
    {
        return new TaxRates([
            new TaxRate(4004, 'DK', BigDecimal::of('0.250'), new DateTimeImmutable('2015-01-01')),
            new TaxRate(4007, 'ES', BigDecimal::of('0.210'), new DateTimeImmutable('2015-01-01')),
            new TaxRate(4010, 'IE', BigDecimal::of('0.210'), new DateTimeImmutable('2020-09-01')),
            new TaxRate(4010, 'IE', BigDecimal::of('0.230'), new DateTimeImmutable('2021-03-01')),
            new TaxRate(4027, 'GB', BigDecimal::of('0.200'), new DateTimeImmutable('2015-01-01')),
        ]);
    }

    protected function assertBigDecimalEquals(string $expected, BigDecimal $actual)
    {
        $this->assertEquals($expected, (string)$actual);
    }
}