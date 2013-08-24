<?php
include('whois.main.php');

class PHPWhoisTest extends PHPUnit_Framework_TestCase {
  function testIncludePath() {
    $includePath = get_include_path();
    $this->assertTrue(strpos($includePath, "lib/phpwhois") != 0, "phpwhois not found in path: " . $includePath);
  }

  function testItalianParser() {
    $sampleFile = file_get_contents(TEST_ROOT . "/it/amazon.it.txt");
    $sampleFile = explode("\n", $sampleFile);

    require_once("whois.it.php");
    $handler = new it_handler();
    $sampleFile = $handler->parse(["rawdata" => $sampleFile], "");
    // print_r($sampleFile);
  }

  function testGermanParser() {
    $sampleFile = file_get_contents(TEST_ROOT . "/de/thelocal.de.txt");
    $sampleFile = explode("\n", $sampleFile);

    require_once("whois.de.php");
    $handler = new de_handler();
    $sampleFile = $handler->parse(["rawdata" => $sampleFile], "");
    // print_r($sampleFile);
  }

  function testPhpwhoisToWhoapi() {
    require('phpwhois2whoapi.php');
    
    // Check for German Parser
    $phpwhoarray = file_get_contents(TEST_ROOT . "/de/idealo.de.txt");
    
    $whois = new PhpwhoisToWhoapi("de");
    $r = $whois->convertToWhoapi($phpwhoarray);

    $expected = array(
      "status" => "success",
      "status_desc" => "Request successful",
      "whois_server" => "whois.denic.de",
      "limit_hit" => false,
      "registered" => true,
      "premium" => false,
      "domain_status" => [],
      "date_created" => "1993-09-22 00:00:00",  // format must be "Y-m-d H:i:s"
      "date_updated" => "2012-11-01 00:00:00",  // format must be "Y-m-d H:i:s"
      "date_expires" => "2018-09-21 00:00:00",  // format must be "Y-m-d H:i:s"
      "date_transferred" => "2013-09-21 00:00:00",  // format must be "Y-m-d H:i:s"
      "contacts" => [
          [
          "type" => "registrar",        // types: registrar, registrant, admin, tech, billing
          "name" => "Full name",
          "organization" => "Company Ltd.",
          "full_address" => "",
          "street" => "",
          "city" => "",
          "zipcode" => "",
          "state" => "",
          "country" => "",
          "phone" => "",
          "fax" => "",
          "email" => ""
          ]
      ],
      "nameservers" => [
          "ns1.p42.dynect.net",
          "ns1.timewarner.net",
          "ns2.p42.dynect.net",
          "ns3.timewarner.net"
      ],
      "disclaimer" => "",           // terms of use whois server has set
      "emails" => ['email1','email2','email3'],  // all emails found in raw data
      "whois_raw" => "",          // original unparsed whois result
    );

    print_r($r);
    // $this->assertEquals($expected, $r, "Parsed array mismatch");
  }
}