<?php
declare(strict_types=1);

namespace App;


use RuntimeException;

class DataParsingTest extends BaseTestCase
{
    public function testReadsEmptyFile()
    {
        $filename = __DIR__ . '/data/headers.csv';
        $reader = new BookEntryReader($filename);

        $this->assertNull($reader->next());
    }

    public function testReadsLinesFromFileWithSingleTransaction()
    {
        $filename = __DIR__ . '/data/single.csv';
        $reader = new BookEntryReader($filename);

        $entry = $reader->next();
        $this->assertEquals(6, $entry->getTransactionId());
        $this->assertEquals(1240, $entry->getAccountNumber());
        $this->assertBigDecimalEquals('3.60', $entry->getAmount());
        $this->assertTrue($entry->isDebit());
        $this->assertEquals('2021-01-01', $entry->getDate()->format('Y-m-d'));

        // ignore second
        $reader->next();

        $entry = $reader->next();
        $this->assertEquals(6, $entry->getTransactionId());
        $this->assertEquals(3818, $entry->getAccountNumber());
        $this->assertBigDecimalEquals('0.62', $entry->getAmount());
        $this->assertFalse($entry->isDebit());

        $this->assertNull($reader->next());
    }

    public function testReadsRevenueFromFileWithSingleTransaction()
    {
        $filename = __DIR__ . '/data/single.csv';
        $parser = new RevenueParser(new BookEntryReader($filename), $this->taxRates());

        $revenue = $parser->next();
        $this->assertEquals(6, $revenue->getTransactionId());
        $this->assertEquals(4007, $revenue->getAccountNumber());
        $this->assertEquals('2021-01-01', $revenue->getDate()->format('Y-m-d'));
        $this->assertBigDecimalEquals('2.98', $revenue->getNetAmount());
        $this->assertBigDecimalEquals('0.62', $revenue->getTax());

        $revenue = $parser->next();
        $this->assertNull($revenue);
    }

    public function testReadsNegativeRevenueFromFileWithTransactionReversal()
    {
        $filename = __DIR__ . '/data/reversal.csv';
        $parser = new RevenueParser(new BookEntryReader($filename), $this->taxRates());

        $revenue = $parser->next();
        $this->assertEquals(4027, $revenue->getAccountNumber());
        $this->assertBigDecimalEquals('-49.17', $revenue->getNetAmount());
        $this->assertBigDecimalEquals('-9.83', $revenue->getTax());
    }

    public function testFailsWhenReadingRevenueFromFileWithInvalidTransaction()
    {
        $filename = __DIR__ . '/data/invalid.csv';
        $parser = new RevenueParser(new BookEntryReader($filename), $this->taxRates());

        try {
            $parser->next();
            $this->fail("Failure expected.");
        } catch (RuntimeException $e) {
            $this->assertEquals('Illegal state: Transaction [6] contains MOSS tax entry, but no matching amount entry exists.', $e->getMessage());
        }
    }

    public function testReadsRevenuesFromFileWithMultipleTransactions()
    {
        $filename = __DIR__ . '/data/multiple.csv';
        $parser = new RevenueParser(new BookEntryReader($filename), $this->taxRates());

        $revenues = [];
        while ($revenue = $parser->next())
            $revenues[] = $revenue;

        $this->assertCount(3, $revenues);

        $this->assertEquals(483, $revenues[2]->getTransactionId());
        $this->assertEquals(4010, $revenues[2]->getAccountNumber());
        $this->assertBigDecimalEquals('7.11', $revenues[2]->getNetAmount());
    }
}