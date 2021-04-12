<?php

use App\App;

require __DIR__ . '/vendor/autoload.php';

$use_text = 'Use: ' . basename(__FILE__) . ' -f "filename" -q quarter [-o output_filename]' . PHP_EOL;

$options = getopt('f:q:o:');
if ($options === false) {
    die($use_text);
}

// Get file name.
if (!isset($options['f']))
    die($use_text);
$filename = $options['f'];

// Check whether file exists.
if (!file_exists($filename))
    die("File '$filename' does not exist.\n");

// Get quarter (if not set, report will be generated for all data).
$quarter = null;
if (isset($options['q'])) {
    $quarter = intval($options['q']);
    if ($quarter < 1 || $quarter > 4)
        die("Invalid value for quarter: $quarter\n");
}

$outputFilename = dirname(__FILE__) . '/report.csv';
if (isset($options['o'])) {
    $outputFilename = $options['o'];
}

try {
    $app = new App($filename, $outputFilename, $quarter);
    $totalTax = $app->run();

    echo "Total tax: {$totalTax}" . PHP_EOL;
} catch (Throwable $e) {
    echo "FAILURE - {$e->getMessage()}" . PHP_EOL;
    exit(1);
}
