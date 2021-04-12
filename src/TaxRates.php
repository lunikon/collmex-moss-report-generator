<?php
declare(strict_types=1);

namespace App;


use Brick\Math\BigDecimal;
use DateTimeImmutable;
use RuntimeException;

class TaxRates
{
    /** @var TaxRate[][] */
    private array $accounts;

    /**
     * TaxRates constructor.
     * @param TaxRate[] $rates
     */
    public function __construct(array $rates)
    {
        $accounts = [];
        foreach ($rates as $rate) {
            $accountNumber = $rate->getAccountNumber();
            if (!isset($accounts[$accountNumber])) {
                $accounts[$accountNumber] = [];
            }
            $accounts[$accountNumber][] = $rate;
        }

        $this->accounts = array_map(function (array $rates) {
            usort($rates, function (TaxRate $a, TaxRate $b) {
                // Sort in descending order by valid from date.
                return $b->getValidFrom() <=> $a->getValidFrom();
            });
            return $rates;
        }, $accounts);
    }

    public static function readFromFile(string $filename): TaxRates
    {
        $rates = [];
        $handle = fopen($filename, 'r');
        while ($row = fgetcsv($handle, 0, "\t")) {
            $rates[] = new TaxRate(
                intval($row[2]),
                $row[1],
                BigDecimal::of($row[3]),
                DateTimeImmutable::createFromFormat('Y-m-d', $row[4])
            );
        }
        fclose($handle);

        return new self($rates);
    }

    public function getByAccountAtDate(int $accountNumber, DateTimeImmutable $date): TaxRate
    {
        $rates = $this->accounts[$accountNumber];
        foreach ($rates as $rate) {
            if ($rate->getValidFrom() > $date)
                continue;

            return $rate;
        }

        throw new RuntimeException("No tax rate for account number $accountNumber and date {$date->format('Y-m-d')}.");
    }

    public function isMOSSAccountNumber(int $accountNumber): bool
    {
        return isset($this->accounts[$accountNumber]);
    }
}