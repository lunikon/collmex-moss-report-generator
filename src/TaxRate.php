<?php
declare(strict_types=1);

namespace App;


use Brick\Math\BigDecimal;
use DateTimeImmutable;

class TaxRate
{
    private string $identifier;

    private int $accountNumber;

    private string $countryCode;

    private BigDecimal $rate;

    private DateTimeImmutable $validFrom;

    /**
     * TaxRate constructor.
     * @param int $accountNumber
     * @param string $countryCode
     * @param BigDecimal $rate
     * @param DateTimeImmutable $validFrom
     */
    public function __construct(int $accountNumber, string $countryCode, BigDecimal $rate, DateTimeImmutable $validFrom)
    {
        $this->accountNumber = $accountNumber;
        $this->countryCode = $countryCode;
        $this->rate = $rate;
        $this->validFrom = $validFrom;

        $this->identifier = "{$this->accountNumber}_{$this->validFrom->format('Y-m-d')}";
    }

    /**
     * @return string
     */
    public function getIdentifier(): string
    {
        return $this->identifier;
    }

    /**
     * @return int
     */
    public function getAccountNumber(): int
    {
        return $this->accountNumber;
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
    public function getRate(): BigDecimal
    {
        return $this->rate;
    }

    /**
     * @return DateTimeImmutable
     */
    public function getValidFrom(): DateTimeImmutable
    {
        return $this->validFrom;
    }
}