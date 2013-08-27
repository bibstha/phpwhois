<?php
class PhpwhoisDETest extends PHPUnit_Framework_TestCase {
  function testIncludePath() {
    $includePath = get_include_path();
    $this->assertTrue(strpos($includePath, "lib/phpwhois") != 0, "phpwhois not found in path: " . $includePath);
  }

  function testGermanStrToPhpwhois() {
    require_once('phpwhois.to.whoapi.php');

    // Test Google
    $sampleFile = file_get_contents(TEST_ROOT . "/de/google.de.txt");
    $whois = new PhpwhoisToWhoapi("de", "whois.denic.de");
    $result = $whois->parseStrToPhpwhois($sampleFile);
    // print_r($result);
    
    $this->assertTrue(isset($result["regrinfo"]["domain"]));
    $this->assertTrue(isset($result["regrinfo"]["tech"]));
    $this->assertTrue(isset($result["regrinfo"]["zone"]));
    $this->assertTrue(isset($result["regyinfo"]));
    $this->assertTrue(isset($result["rawdata"]), "Rawdata missing in Result");
    $this->assertTrue(isset($result["regrinfo"]["disclaimer"]), "Disclaimer missing in Result");
    $this->assertEquals("2011-03-30 19:36:27", $result["regrinfo"]["domain"]["changed"]);

    // Test Idealo with different emails
    $sampleFile = file_get_contents(TEST_ROOT . "/de/idealo.de.txt");
    $whois = new PhpwhoisToWhoapi("de", "whois.denic.de");
    $result = $whois->parseStrToPhpwhois($sampleFile);
    
    $this->assertTrue(isset($result["regrinfo"]["domain"]));
    $this->assertTrue(isset($result["regrinfo"]["tech"]));
    $this->assertTrue(isset($result["regrinfo"]["zone"]));
    $this->assertTrue(isset($result["regyinfo"]));
    $this->assertTrue(isset($result["rawdata"]), "Rawdata missing in Result");
    $this->assertTrue(isset($result["regrinfo"]["disclaimer"]), "Disclaimer missing in Result");
  }

  function testWhoapiHasEmails() {
    require_once('phpwhois.to.whoapi.php');

    // Test Google German
    $sampleFile = file_get_contents(TEST_ROOT . "/de/google.de.txt");
    $whois = new PhpwhoisToWhoapi("de", "whois.denic.de");
    $result = $whois->convertToWhoapi($sampleFile);
    $this->assertTrue(isset($result["emails"]), "Emails missing in Result");
    $this->assertTrue(in_array("dns-admin@google.com", $result["emails"]));
    $this->assertTrue(in_array("ccops@markmonitor.com", $result["emails"]));

    // Test Idealo German
    $sampleFile = file_get_contents(TEST_ROOT . "/de/idealo.de.txt");
    $result = $whois->convertToWhoapi($sampleFile);
    $this->assertTrue(isset($result["emails"]), "Emails missing in Result");
    $this->assertTrue(in_array("domains@idealo.de", $result["emails"]));
    $this->assertTrue(count($result["emails"]) == 1, "Ideal should have one email, found " . count($result["emails"]));
  }

  function testPhpwhoisToWhoapi() {
    require_once('phpwhois.to.whoapi.php');
    
    // Check for German Parser
    $phpwhoarray = file_get_contents(TEST_ROOT . "/de/google.de.txt");
    
    $whois = new PhpwhoisToWhoapi("de", "whois.denic.de");
    $r = $whois->convertToWhoapi($phpwhoarray);

    $expected = array(
      "status" => "success",
      "status_desc" => "Request successful",
      "whois_server" => "whois.denic.de",
      "limit_hit" => false,
      "registered" => true,
      "premium" => false,
      "domain_status" => [],
      "date_created" => "",  // format must be "Y-m-d H:i:s"
      "date_updated" => "2011-03-30 19:36:27",  // format must be "Y-m-d H:i:s"
      "date_expires" => "",  // format must be "Y-m-d H:i:s"
      // "date_transferred" => "",  // format must be "Y-m-d H:i:s"
      "contacts" => [
          [
          "type" => "zone",        // types: registrar, registrant, admin, tech, billing
          "name" => "Domain Admin",
          "organization" => "MarkMonitor Inc",
          "street" => "391 N Ancestor Pl",
          "city" => "Boise",
          "zipcode" => "83704",
          // "state" => "",
          "country" => "US",
          "phone" => "+1.2083895740",
          "fax" => "+1.2083895771",
          "email" => "ccops@markmonitor.com",
          "full_address" => "MarkMonitor Inc\n391 N Ancestor Pl\nBoise\n83704\nUS"
          ],
          [
          "type" => "tech",        // types: registrar, registrant, admin, tech, billing
          "name" => "DNS Admin",
          "organization" => "Google Inc.",
          "street" => "1600 Amphitheatre Parkway",
          "city" => "Mountain View",
          "zipcode" => "94043",
          // "state" => "",
          "country" => "US",
          "phone" => "+1.6502530000",
          "fax" => "+1.6506188571",
          "email" => "dns-admin@google.com",
          "full_address" => "Google Inc.\n1600 Amphitheatre Parkway\nMountain View\n94043\nUS"
          ]
      ],
      "nameservers" => [
        "ns1.google.com",
        "ns2.google.com",
        "ns3.google.com",
        "ns4.google.com",
      ],
      "disclaimer" => "",           // terms of use whois server has set
      "emails" => ['ccops@markmonitor.com','dns-admin@google.com'],  // all emails found in raw data
      "whois_raw" => "",          // original unparsed whois result
    );

    // print_r($r);
    $r["disclaimer"] = "";
    $r["whois_raw"] = "";
    $this->assertEquals($expected, $r, "Parsed array mismatch");
  }

  function testNotFoundDomain() {
    require_once('phpwhois.to.whoapi.php');
    $raw = file_get_contents("de/notfound.txt");
    $whois = new PhpwhoisToWhoapi("de", "whois.denic.de");
    $result = $whois->convertToWhoapi($raw);

    $expected = array(
      "status" => "success",        // put 'error' on any unexpected problem & describe it below
      "status_desc" => "Request successful",
      "whois_server" => "whois.denic.de",         // domain or address where we got the data from
      "limit_hit" => false,         // only 'true' when the whois limit is hit
      "registered" => false,
      "disclaimer" => "",       // terms of use whois server has set
      "whois_raw" => "Domain: random1234.de
Status: free
",  // original unparsed whois result
    );
    $this->assertEquals($expected, $result);
  }

  function testLimitHit() {
    require_once('phpwhois.to.whoapi.php');
    $raw = file_get_contents("de/limit.txt");
    $whois = new PhpwhoisToWhoapi("de", "whois.denic.de");
    $result = $whois->convertToWhoapi($raw);

    $expected = array(
      "status" => "success",        // put 'error' on any unexpected problem & describe it below
      "status_desc" => "Request successful",
      "whois_server" => "",       // domain or address where we got the data from
      "limit_hit" => true,        // set to 'true' as we hit the limit
      "registered" => null,
      "disclaimer" => "",       // terms of use whois server has set
      "whois_raw" => "% Error: 55000000002 Connection refused; access control limit exceeded
",        // original unparsed whois result
    );

    $this->assertEquals($expected, $result);
  }
}