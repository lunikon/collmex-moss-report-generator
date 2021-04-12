<?php
declare(strict_types=1);

namespace App;

use Brick\Math\BigDecimal;
use DateTimeImmutable;
use Exception;
use PHPUnit\Framework\MockObject\MockObject;

class GeneratorTest extends BaseTestCase
{
    private RevenueParser|MockObject $revenues;

    public function testGeneratesEmptyOutputData()
    {
        $this->mockRevenues();

        $generator = new Generator($this->taxRates());
        $output = $generator->generate($this->revenues);

        $this->assertIsArray($output);
        $this->assertCount(0, $output);
    }

    public function testSingleRevenueLeadsToSingleTaxLine()
    {
        $this->mockRevenues(
            new Revenue(1, 4010, new DateTimeImmutable('2021-01-07'), BigDecimal::of('7.11'), BigDecimal::of('1.49'))
        );

        $generator = new Generator($this->taxRates());
        $output = $generator->generate($this->revenues);

        $this->assertIsArray($output);
        $this->assertCount(1, $output);
        $this->assertTaxLine('IE', '0.210', '7.11', '1.49', $output[0]);
    }

    public function testMultipleRevenuesLeadToSingleTaxLine()
    {
        $this->mockRevenues(
            new Revenue(1, 4010, new DateTimeImmutable('2021-01-06'), BigDecimal::of('7.11'), BigDecimal::of('1.49')),
            new Revenue(2, 4010, new DateTimeImmutable('2021-01-07'), BigDecimal::of('7.11'), BigDecimal::of('1.49'))
        );

        $generator = new Generator($this->taxRates());
        $output = $generator->generate($this->revenues);

        $this->assertIsArray($output);
        $this->assertCount(1, $output);
        $this->assertTaxLine('IE', '0.210', '14.22', '2.98', $output[0]);
    }

    public function testRevenuesInDifferentCountriesLeadToDifferentTaxLines()
    {
        $this->mockRevenues(
            new Revenue(1, 4010, new DateTimeImmutable('2021-01-06'), BigDecimal::of('7.11'), BigDecimal::of('1.49')),
            new Revenue(2, 4004, new DateTimeImmutable('2021-01-11'), BigDecimal::of('2.88'), BigDecimal::of('0.72'))
        );

        $generator = new Generator($this->taxRates());
        $output = $generator->generate($this->revenues);

        $this->assertIsArray($output);
        $this->assertCount(2, $output);
        $this->assertTaxLine('IE', '0.210', '7.11', '1.49', $output[0]);
        $this->assertTaxLine('DK', '0.250', '2.88', '0.72', $output[1]);
    }

    public function testMultipleRevenuesInSameCountryLeadToMultipleTaxLineForDifferentRates()
    {
        $this->mockRevenues(
            new Revenue(1, 4010, new DateTimeImmutable('2021-01-06'), BigDecimal::of('7.11'), BigDecimal::of('1.49')),
            new Revenue(2, 4010, new DateTimeImmutable('2021-04-10'), BigDecimal::of('50.73'), BigDecimal::of('11.76'))
        );

        $generator = new Generator($this->taxRates());
        $output = $generator->generate($this->revenues);

        $this->assertIsArray($output);
        $this->assertCount(2, $output);
        $this->assertTaxLine('IE', '0.210', '7.11', '1.49', $output[0]);
        $this->assertTaxLine('IE', '0.230', '50.73', '11.76', $output[1]);
    }

    public function testGeneratorCanFilterByQuarter()
    {
        $this->mockRevenues(
            new Revenue(1, 4010, new DateTimeImmutable('2021-01-06'), BigDecimal::of('7.11'), BigDecimal::of('1.49')),
            new Revenue(2, 4010, new DateTimeImmutable('2021-04-10'), BigDecimal::of('50.73'), BigDecimal::of('11.76'))
        );

        $generator = new Generator($this->taxRates());
        $generator->setRevenueFilter(new QuarterRevenueFilter(1));
        $output = $generator->generate($this->revenues);

        $this->assertIsArray($output);
        $this->assertCount(1, $output);
        $this->assertTaxLine('IE', '0.210', '7.11', '1.49', $output[0]);
    }

    public function testFailureOnTaxRateMismatch()
    {
        $this->mockRevenues(
            new Revenue(1, 4010, new DateTimeImmutable('2021-01-06'), BigDecimal::of('50.73'), BigDecimal::of('11.76'))
        );

        $generator = new Generator($this->taxRates());
        try {
            $generator->generate($this->revenues);
            $this->fail("Failure expected.");
        } catch (Exception $e) {
            $this->assertEquals('Record 1: Actual tax rate [0.230] does not match expected [0.210].', $e->getMessage());
        }
    }

    protected function mockRevenues(Revenue ...$revenues): void
    {
        $this->revenues = $this->createMock(RevenueParser::class);
        $this->revenues->method('next')->will($this->onConsecutiveCalls(...$revenues));
    }

    protected function assertTaxLine(string $expectedCountryCode, string $expectedTaxRate, string $expectedNetAmount, string $expectedTaxAmount, TaxLine $taxLine): void
    {
        $this->assertEquals($expectedCountryCode, $taxLine->getCountryCode());
        $this->assertBigDecimalEquals($expectedTaxRate, $taxLine->getTaxRate());
        $this->assertBigDecimalEquals($expectedNetAmount, $taxLine->getNetAmount());
        $this->assertBigDecimalEquals($expectedTaxAmount, $taxLine->getTaxAmount());
    }
}