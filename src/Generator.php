<?php
declare(strict_types=1);

namespace App;

use RuntimeException;

class Generator
{
    private TaxRates $taxRates;

    private RevenueFilter $revenueFilter;

    /**
     * Generator constructor.
     * @param TaxRates $taxRates
     */
    public function __construct(TaxRates $taxRates)
    {
        $this->taxRates = $taxRates;
        $this->revenueFilter = new class implements RevenueFilter {
            function filter(Revenue $revenue): bool
            {
                return true;
            }
        };
    }

    /**
     * @param RevenueParser $revenues
     * @return TaxLine[]
     */
    public function generate(RevenueParser $revenues): array
    {
        $taxLines = [];
        while ($revenue = $revenues->next()) {
            if (!$this->revenueFilter->filter($revenue))
                continue;

            $taxRate = $this->taxRates->getByAccountAtDate($revenue->getAccountNumber(), $revenue->getDate());
            $lineId = $taxRate->getIdentifier();
            if (!isset($taxLines[$lineId])) {
                $taxLines[$lineId] = new TaxLine($taxRate);
            }

            $computedTaxRate = $revenue->getComputedTaxRate();
            if ($computedTaxRate->compareTo($taxRate->getRate()) !== 0)
                throw new RuntimeException("Record {$revenue->getTransactionId()}: Actual tax rate [{$computedTaxRate}] does not match expected [{$taxRate->getRate()}].");

            $taxLines[$lineId]->add($revenue);
        }
        return array_values($taxLines);
    }

    public function setRevenueFilter(RevenueFilter $filter)
    {
        $this->revenueFilter = $filter;
    }
}