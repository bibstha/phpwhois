Phpwhois to Whoapi
==================

Custom modification on phpwhois to parse and convert whois data into whoapi format.

## How to use?

The library can be used for 

1. Extraction raw Whois data from a number of different whois servers.
2. It can parse the data and convert to whoapi format

## Example: Raw data extraction

    require_once('/path/to/folder/lib/phpwhois/phpwhois.query.php');
    $queryObj = new PhpwhoisQuery();
    $data = $queryObj->fetch($domain);
    // $data contains raw string whois data

## Example: Parsing data

    require_once('/path/to/folder/lib/phpwhois/phpwhois.to.whoapi.php');
    $whois = new PhpwhoisToWhoapi("de", "whois.denic.de");
    $data = $whois->convertToWhoapi($data);
    // $data contains parsed whois as an Array