<?php
declare(strict_types=1);

namespace App;


use Brick\Math\BigDecimal;

class TaxLine
{
    private string $countryCode;

    private BigDecimal $taxRate;

    private BigDecimal $netAmount;

    private BigDecimal $taxAmount;

    public function __construct(TaxRate $taxRate)
    {
        $this->countryCode = $taxRate->getCountryCode();
        $this->taxRate = $taxRate->getRate();
        $this->netAmount = BigDecimal::zero();
        $this->taxAmount = BigDecimal::zero();
    }

    public function add(Revenue $revenue): void
    {
        $this->netAmount = $this->netAmount->plus($revenue->getNetAmount());
        $this->taxAmount = $this->taxAmount->plus($revenue->getTax());
    }

    /**
     * @return string
     */
    public function getCountryCode(): string
    {
        return $this->countryCode;
    }

    /**
     * @return BigDecimal
     */
    public function getTaxRate(): BigDecimal
    {
        return $this->taxRate;
    }

    /**
     * @return BigDecimal
     */
    public function getNetAmount(): BigDecimal
    {
        return $this->netAmount;
    }

    /**
     * @return BigDecimal
     */
    public function getTaxAmount(): BigDecimal
    {
        return $this->taxAmount;
    }
}