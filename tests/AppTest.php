<?php
declare(strict_types=1);

namespace App;


class AppTest extends BaseTestCase
{
    private string $outputFilename = __DIR__ . '/data/output.csv';

    public function testProcessesEmptyFile()
    {
        $filename = __DIR__ . '/data/headers.csv';

        $app = new App($filename, $this->outputFilename);
        $app->run();

        $this->assertFileEquals(__DIR__ . '/data/expected_output_empty.csv', $this->outputFilename);
    }

    public function testProcessesFileWithSingleTransaction()
    {
        $filename = __DIR__ . '/data/single.csv';

        $app = new App($filename, $this->outputFilename);
        $app->run();

        $this->assertFileEquals(__DIR__ . '/data/expected_output_single.csv', $this->outputFilename);
    }

    protected function tearDown(): void
    {
        if (file_exists($this->outputFilename))
            unlink($this->outputFilename);
    }
}