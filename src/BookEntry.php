<?php
declare(strict_types=1);

namespace App;


use Brick\Math\BigDecimal;
use DateTimeImmutable;

class BookEntry
{
    private int $transactionId;

    private int $accountNumber;

    private BigDecimal $amount;

    private bool $debit;

    private DateTimeImmutable $date;

    public static function fromCollmexExportLine(array $row): static
    {
        $entry = new static();
        $entry->transactionId = intval($row['BuchungNr']);
        $entry->accountNumber = intval($row['Konto']);
        $entry->amount = self::parseCollmexAmount($row['Betrag']);
        $entry->debit = $row['S/H'] === 'Soll';
        $entry->date = DateTimeImmutable::createFromFormat('d.m.Y', $row['Belegdatum']);
        return $entry;
    }

    /**
     * The Collmex export holds numbers in German format (comma instead of period as decimal separator). Also, credit
     * values are stored as negative numbers, so we use the absolute value.
     *
     * @param string $value
     * @return BigDecimal
     */
    private static function parseCollmexAmount(string $value): BigDecimal
    {
        $cleaned = str_replace(',', '.', $value);
        return BigDecimal::of($cleaned)->abs();
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
     * @return BigDecimal
     */
    public function getAmount(): BigDecimal
    {
        return $this->amount;
    }

    /**
     * @return bool
     */
    public function isDebit(): bool
    {
        return $this->debit;
    }

    /**
     * @return DateTimeImmutable
     */
    public function getDate(): DateTimeImmutable
    {
        return $this->date;
    }
}