<?php
declare(strict_types=1);

namespace App;


use Brick\Math\BigDecimal;

class App
{
    private string $filename;

    private string $outputFilename;

    private ?int $quarter;

    public function __construct(string $filename, string $outputFilename, int|null $quarter = null)
    {
        $this->filename = $filename;
        $this->outputFilename = $outputFilename;
        $this->quarter = $quarter;
    }

    public function run(): BigDecimal
    {
        // TODO hard-coded accounts file
        $taxRates = TaxRates::readFromFile(__DIR__ . '/../accounts.tsv');

        $reader = new BookEntryReader($this->filename);
        $revenueParser = new RevenueParser($reader, $taxRates);

        $generator = new Generator($taxRates);
        if ($this->quarter !== null)
            $generator->setRevenueFilter(new QuarterRevenueFilter($this->quarter));
        $taxLines = $generator->generate($revenueParser);

        $this->write($taxLines);

        return array_reduce($taxLines, function (BigDecimal $sum, TaxLine $taxLine) {
            return $sum->plus($taxLine->getTaxAmount());
        }, BigDecimal::zero());
    }

    /**
     * @param TaxLine[] $taxLines
     */
    private function write(array $taxLines): void
    {
        usort($taxLines, function (TaxLine $a, TaxLine $b) {
            return $a->getCountryCode() <=> $b->getCountryCode();
        });

        $output = [
            [
                "Land des Verbrauchs",
                "Umsatzsteuertyp",
                "Umsatzsteuersatz",
                "Steuerbemessungs-Grundlage",
                "Umsatzsteuerbetrag"
            ]
        ];
        foreach ($taxLines as $taxLine) {
            $output[] = [
                $taxLine->getCountryCode(),
                "STANDARD",
                $taxLine->getTaxRate()->multipliedBy(100)->toScale(2),
                $taxLine->getNetAmount(),
                $taxLine->getTaxAmount(),
            ];
        }

        if (($handle = fopen($this->outputFilename, 'w')) !== false) {
            foreach ($output as $fields) {
                fputcsv($handle, $fields);
            }
            fclose($handle);
        }
    }
}