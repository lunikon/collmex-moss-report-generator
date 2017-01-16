<?php
$use_text = 'Use: ' . basename(__FILE__) . ' -f "filename" -q quarter [-o output_filename]' . PHP_EOL;

$options = getopt('f:q:o:');
if ($options === FALSE) {
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

$output_filename = dirname(__FILE__) . '/report.csv';
if (isset($options['o'])) {
    $output_filename = $options['o'];
}

// Load accounts.
$accounts = array();
if (($handle = fopen(dirname(__FILE__) . '/accounts.tsv', 'r')) !== FALSE) {
    while (($row = fgetcsv($handle, 0, "\t")) !== FALSE) {
        $account = new stdClass();
        $account->number = $row[2];
        $account->country_code = $row[1];
        $account->tax_percentage = $row[3];
        $accounts[$account->number] = $account;
    }
    fclose($handle);
} else {
    die("Failed to load accounts file.\n");
}

// The Collmex export holds numbers in German format (comma instead of period as decimal separator).
function parse_number($value)
{
    return floatval(str_replace(',', '.', $value));
}

// Prepare results array.
$results = array();
foreach ($accounts as $account) {
    $res = new stdClass();
    $res->country_code = $account->country_code;
    $res->tax_rate = $account->tax_percentage * 100;
    $res->base = 0;
    $res->tax = 0;
    $results[$account->number] = $res;
}

// Open and parse file.
if (($handle = fopen($filename, 'r')) !== FALSE) {
    $indexes = null;
    $i = 0;
    $record_id = null;
    $current_account = null;
    $current_amount = null;
    while (($row = fgetcsv($handle, 0, ';')) !== FALSE) {
        // Use first row to prepare row indexes.
        if ($i === 0) {
            $indexes = array();
            $num = count($row);
            for ($c = 0; $c < $num; $c++) {
                $indexes[$row[$c]] = $c;
            }
        } else {
            $r = $row[$indexes['BuchungNr']];

            // Reset state whenever new record starts.
            if ($r !== $record_id) {
                if ($current_account !== null)
                    echo "Record $record_id: No matching tax line for account $current_account.\n";

                $record_id = $r;
                $current_account = null;
                $current_amount = null;
            }

            // Check whether record belongs to the selected quarter.
            if ($quarter !== null) {
                $date = date_parse($row[$indexes['Belegdatum']]);
                $q = ceil($date['month'] / 3);
                if ($q != $quarter)
                    continue;
            }

            // If previous line was a relevant account, search for matching tax line.
            $account_number = $row[$indexes['Konto']];
            if ($current_account !== null && $account_number == '3818') {
                // Check whether tax amount corresponds to tax level from account list.
                $account = $accounts[$current_account];

                $parsed_tax = parse_number($row[$indexes['Betrag']]);
                $gross = $current_amount + $parsed_tax;
                $calculated_tax = round($gross * $account->tax_percentage / (1 + $account->tax_percentage), 2);

                $diff = abs(round(($parsed_tax - $calculated_tax) * 100));
                if ($diff > 1) {
                    // Differences up to 1 cent are seen as acceptable rounding errors.
                    echo "Record $record_id: Parsed ($parsed_tax) and calculated ($calculated_tax) tax amounts differ by $diff cents. Using calculated amount.\n";
                    $base = $gross - $calculated_tax;
					$tax = $calculated_tax;
                } else {
                    $base = $current_amount;
                    $tax = $parsed_tax;
                }

				$result = $results[$current_account];
				$result->base += $base;
                $result->tax += $tax;

                $current_account = null;
                $current_amount = null;
            } // Otherwise, record is only relevant when there are MOSS accounts involved.
            else if ($current_account === null && array_key_exists($account_number, $accounts)) {
                $current_account = $account_number;
                $current_amount = parse_number($row[$indexes['Betrag']]);
            }
        }

        $i++;
    }
    fclose($handle);
} else {
    die("Failed to open filename '$filename'.\n");
}

// Prepare output.
$output = array();
$output[] = array(
    "Land des Verbrauchs",
    "Umsatzsteuertyp",
    "Umsatzsteuersatz",
    "Steuerbemessungs-Grundlage",
    "Umsatzsteuerbetrag"
);
foreach ($results as $result) {
    if ($result->base == 0)
        continue;

    $output[] = array(
        $result->country_code,
        "STANDARD",
        number_format($result->tax_rate, 2),
        number_format($result->base * -1, 2, '.', ''), // Credit values are negative in Collmex export.
        number_format($result->tax * -1, 2, '.', '') // Credit values are negative in Collmex export.
    );
}

// Write output.
if (($handle = fopen($output_filename, 'w')) !== FALSE) {
    foreach ($output as $fields) {
        fputcsv($handle, $fields);
    }
    fclose($handle);
} else {
    die("Failed to open output file '$output_filename' for writing.\n");
}

// Echo total tax amount.
$total_tax = array_reduce($results, function($carry, $item) {
    return $carry + $item->tax;
}, 0);
echo "Total tax: " . number_format(-$total_tax, 2) . PHP_EOL;
