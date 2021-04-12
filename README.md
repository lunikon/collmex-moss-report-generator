# Collmex MOSS Report Generator

A script that generates a valid MOSS (Mini-One-Stop-Shop) report from a Collmex record export file.

## Disclaimer

This script comes with no warranties whatsoever (see licence). It only works with a certain input file format
(files exported from the [Collmex accounting software](https://collmex.de/)) and exports to the format required by the
German *Bundeszentralamt für Steuern* for filing MOSS tax declarations.

## How to use

To run the script, you need to have a working installation of PHP 8 and Composer or you can use the Docker Compose file
that comes with the project.

Also, in Collmex, you need to use the accounting system they recommend for MOSS-transactions. You can find more details
on this (in German)
here: [Ausländische Erlösarten](https://collmex.de/cgi-bin/cgi.exe?1005,1,help,auslaendische_erloesarten). The account
numbers, country codes, tax rates and validity dates of the tax rates are stored in the tab-separated file
```accounts.tsv```. Modify this file to adjust it your accounting scheme.

Install the dependencies first:

```
composer install
```

Then run the script like so:

```
php report.php -f input.csv
```

```input.csv``` refers to a file as exported from Collmex.

Optional parameters are:

```
-q      Specify the fiscal quarter for which to generate the report. 
        Defaults to empty (generate report for all input data).
-o      Output file name. Defaults to report.csv in the script folder.
```

