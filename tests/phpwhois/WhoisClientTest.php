<?php
require_once('phpwhois.query.php');
class WhoisClientTest extends PHPUnit_Framework_TestCase {
  function testGetRawData() {
    $domain = "idealo.de";
    $whois = new PhpwhoisQuery();
    $query_params = $whois->WhoisQueryParams($domain);
    $result = $whois->FetchRawData($query_params);
    // print_r($result);
  }

  function testWhoisQueryParams() {
    $domain = "google.ws";
    $whois = new PhpwhoisQuery();
    $query_params = $whois->WhoisQueryParams($domain);
    // print_r($query_params);
  }

  function testFetchRawData() {
    $domain = "google.ws";
    $whois = new PhpwhoisQuery();
    $data = $whois->WhoisQueryParams($domain);
    $result = $whois->FetchRawData($data);
    // print_r($data); 
  }
}