<?php
declare(strict_types=1);

namespace App;


use Brick\Math\BigDecimal;
use Brick\Math\RoundingMode;
use DateTimeImmutable;

class Revenue
{
    private int $transactionId;

    private int $accountNumber;

    private DateTimeImmutable $date;

    private BigDecimal $netAmount;

    private BigDecimal $tax;

    /**
     * Revenue constructor.
     * @param int $transactionId
     * @param int $accountNumber
     * @param DateTimeImmutable $date
     * @param BigDecimal $netAmount
     * @param BigDecimal $tax
     */
    public function __construct(int $transactionId, int $accountNumber, DateTimeImmutable $date, BigDecimal $netAmount, BigDecimal $tax)
    {
        $this->transactionId = $transactionId;
        $this->accountNumber = $accountNumber;
        $this->date = $date;
        $this->netAmount = $netAmount;
        $this->tax = $tax;
    }

    public function getComputedTaxRate(): BigDecimal
    {
        // The scaling here is a bit of a hack, but no tax rate with actual 3-digit precision exists at this time.
        return $this->tax->dividedBy($this->netAmount, 2, RoundingMode::HALF_UP)->toScale(3);
    }

    /**
     * @return int
     */
    public function getTransactionId(): int
    {
        return $this->transactionId;
    }

    /**
     * @return int
     */
    public function getAccountNumber(): int
    {
        return $this->accountNumber;
    }

    /**
     * @return DateTimeImmutable
     */
    public function getDate(): DateTimeImmutable
    {
        return $this->date;
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
    public function getTax(): BigDecimal
    {
        return $this->tax;
    }
}