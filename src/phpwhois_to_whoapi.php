#!/usr/local/bin/php -n
<?php
include("phpwhois/whois.main.php");

$domain = "amazon.it";

$whois = new Whois();
$result = $whois->Lookup($domain);
print_r($result);

