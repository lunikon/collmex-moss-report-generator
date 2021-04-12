<?php
declare(strict_types=1);

namespace App;


use RuntimeException;

class RevenueParser
{
    private const TAX_ACCOUNT_NUMBER = 3818;

    private BookEntryReader $entries;

    private TaxRates $taxRates;

    private ?BookEntry $currentEntry;

    public function __construct(BookEntryReader $entries, TaxRates $taxRates)
    {
        $this->entries = $entries;
        $this->taxRates = $taxRates;

        $this->currentEntry = $entries->next();
    }

    public function next(): ?Revenue
    {
        while ($this->currentEntry !== null) {
            $revenue = $this->parseCurrentTransaction();
            if ($revenue !== null)
                return $revenue;
        }
        return null;
    }

    private function parseCurrentTransaction(): ?Revenue
    {
        $transactionId = $this->currentEntry->getTransactionId();
        $amountEntry = null;
        $taxEntry = null;
        while ($this->currentEntry !== null && $this->currentEntry->getTransactionId() === $transactionId) {
            $accountNumber = $this->currentEntry->getAccountNumber();
            if ($accountNumber === self::TAX_ACCOUNT_NUMBER)
                $taxEntry = $this->currentEntry;
            else if ($this->taxRates->isMOSSAccountNumber($accountNumber))
                $amountEntry = $this->currentEntry;

            $this->currentEntry = $this->entries->next();
        }

        return $this->entriesToRevenue($amountEntry, $taxEntry);
    }

    private function entriesToRevenue(?BookEntry $amountEntry, ?BookEntry $taxEntry): ?Revenue
    {
        if ($amountEntry !== null && $taxEntry !== null) {
            $amount = $amountEntry->getAmount();
            $tax = $taxEntry->getAmount();
            if ($amountEntry->isDebit()) {
                $amount = $amount->negated();
                $tax = $tax->negated();
            }
            return new Revenue($amountEntry->getTransactionId(), $amountEntry->getAccountNumber(), $amountEntry->getDate(), $amount, $tax);
        } else if ($taxEntry !== null)
            throw new RuntimeException("Illegal state: Transaction [{$taxEntry->getTransactionId()}] contains MOSS tax entry, but no matching amount entry exists.");
        else
            return null;
    }
}