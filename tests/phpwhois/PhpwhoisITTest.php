<?php
class PhpwhoisITTest extends PHPUnit_Framework_TestCase {

  function _getDate($format, $dateStr) {
    return strftime($format, strtotime($dateStr));
  }

  function testIncludePath() {
    $includePath = get_include_path();
    $this->assertTrue(strpos($includePath, "lib/phpwhois") != 0, "phpwhois not found in path: " . $includePath);
  }

  function testItalianStrToPhpwhois() {
    require_once('phpwhois.to.whoapi.php');

    // Test Google
    $sampleFile = file_get_contents(TEST_ROOT . "/it/amazon.it.txt");
    $whois = new PhpwhoisToWhoapi("it", "www.nic.it");
    $result = $whois->parseStrToPhpwhois($sampleFile);
    // print_r($result);
    
    $this->assertTrue(isset($result["regrinfo"]["owner"]));
    $this->assertTrue(isset($result["regrinfo"]["tech"]));
    $this->assertTrue(isset($result["regrinfo"]["admin"]));
    $this->assertTrue(isset($result["regyinfo"]));
    $this->assertTrue(isset($result["rawdata"]), "Rawdata missing in Result");
    $this->assertFalse(isset($result["regrinfo"]["disclaimer"]), "Disclaimer should be missing in Result");
    $this->assertEquals("2000-02-10 00:00:00", $result["regrinfo"]["domain"]["created"]);
    $this->assertEquals("2013-01-28 00:59:15", $result["regrinfo"]["domain"]["changed"]);
    $this->assertEquals("2014-01-12 00:00:00", $result["regrinfo"]["domain"]["expires"]);

    // Test Idealo with different emails
    $sampleFile = file_get_contents(TEST_ROOT . "/it/idealo.it.txt");
    $whois = new PhpwhoisToWhoapi("it", "www.nic.it");
    $result = $whois->parseStrToPhpwhois($sampleFile);
    
    $this->assertTrue(isset($result["regrinfo"]["owner"]));
    $this->assertTrue(isset($result["regrinfo"]["tech"]));
    $this->assertTrue(isset($result["regrinfo"]["admin"]));
    $this->assertTrue(isset($result["regyinfo"]));
    $this->assertTrue(isset($result["rawdata"]), "Rawdata missing in Result");
    $this->assertFalse(isset($result["regrinfo"]["disclaimer"]), "Disclaimer should be missing in Result");
  }

  function testWhoapiHasEmails() {
    require_once('phpwhois.to.whoapi.php');

    // Test Google Italian
    $sampleFile = file_get_contents(TEST_ROOT . "/it/amazon.it.txt");
    $whois = new PhpwhoisToWhoapi("it", "www.nic.it");
    $result = $whois->convertToWhoapi($sampleFile);
    $this->assertTrue(isset($result["emails"]), "Emails missing in Result");
    $this->assertTrue(count($result["emails"]) === 0, "No emails, found " . count($result["emails"]));

    // Test Idealo Italian
    $sampleFile = file_get_contents(TEST_ROOT . "/it/idealo.it.txt");
    $result = $whois->convertToWhoapi($sampleFile);
    $this->assertTrue(isset($result["emails"]), "Emails missing in Result");
    $this->assertTrue(count($result["emails"]) === 0, "No emails, found " . count($result["emails"]));
  }

  function testPhpwhoisToWhoapi() {
    require_once('phpwhois.to.whoapi.php');
    
    // Check for Italian Parser
    $phpwhoarray = file_get_contents(TEST_ROOT . "/it/amazon.it.txt");
    
    $whois = new PhpwhoisToWhoapi("it", "www.nic.it");
    $r = $whois->convertToWhoapi($phpwhoarray);

    $expected = array(
      "status" => "success",
      "status_desc" => "Request successful",
      "whois_server" => "www.nic.it",
      "limit_hit" => false,
      "registered" => true,
      "premium" => false,
      "domain_status" => [],
      "date_created" => "2000-02-10 00:00:00",  // format must be "Y-m-d H:i:s"
      "date_updated" => "2013-01-28 00:59:15",  // format must be "Y-m-d H:i:s"
      "date_expires" => "2014-01-12 00:00:00",  // format must be "Y-m-d H:i:s"
      "date_transferred" => "",  // format must be "Y-m-d H:i:s"
      "contacts" => [
          [
          "type" => "registrant",        // types: registrar, registrant, admin, tech, billing
          "name" => "Amazon Europe Holding Technologies SCS",
          "organization" => "Amazon Europe Holding Technologies SCS",
          "full_address" => "",
          "street" => "65, boulevard Grande-Duchesse Charlotte",
          "city" => "Luxembourg City",
          "zipcode" => "1311",
          "state" => "LU",
          "country" => "LU",
          ],
          [
          "type" => "admin",        // types: registrar, registrant, admin, tech, billing
          "name" => "Jocelyn Krabbenschmidt",
          "organization" => "Amazon Europe Holding Technologies SCS",
          "full_address" => "",
          "street" => "65, boulevard Grande-Duchesse Charlotte",
          "city" => "Luxembourg City",
          "zipcode" => "1311",
          "state" => "LU",
          "country" => "LU",
          ],
          [
          "type" => "tech",        // types: registrar, registrant, admin, tech, billing
          "name" => "Amazon Hostmaster",
          "organization" => "Amazon.com, Inc.",
          "full_address" => "",
          "street" => "PO BOX 81226",
          "city" => "Seattle",
          "zipcode" => "98108-1300",
          "state" => "WA",
          "country" => "US",
          ],
          [
          "type" => "registrar",        // types: registrar, registrant, admin, tech, billing
          "name" => "ANCHOVY-REG",
          "organization" => "Hogan Lovells (Paris) LLP",
          "full_address" => "",
          // "street" => "PO BOX 81226",
          // "city" => "Seattle",
          // "zipcode" => "98108-1300",
          // "state" => "WA",
          // "country" => "US",
          ]
      ],
      "nameservers" => [
        "pdns1.ultradns.net",
        "pdns3.ultradns.org",
        "pdns4.ultradns.org",
        "pdns5.ultradns.info",
        "ns1.p31.dynect.net",
        "ns2.p31.dynect.net",
      ],
      "disclaimer" => "",           // terms of use whois server has set
      "emails" => [],  // all emails found in raw data
      "whois_raw" => "",          // original unparsed whois result
    );

    // print_r($r);
    $r["disclaimer"] = "";
    $r["whois_raw"] = "";
    $this->assertEquals($expected, $r, "Parsed array mismatch");
  }

  function testNotFoundDomain() {
    require_once('phpwhois.to.whoapi.php');
    $raw = file_get_contents("it/notfound.txt");
    $whois = new PhpwhoisToWhoapi("it", "www.nic.it");
    $result = $whois->convertToWhoapi($raw);

    $expected = array(
      "status" => "success",        // put 'error' on any unexpected problem & describe it below
      "status_desc" => "Request successful",
      "whois_server" => "www.nic.it",         // domain or address where we got the data from
      "limit_hit" => false,         // only 'true' when the whois limit is hit
      "registered" => false,
      "disclaimer" => "",       // terms of use whois server has set
      "whois_raw" => "Domain:             random12345.it
Status:             AVAILABLE
",  // original unparsed whois result
    );
    $this->assertEquals($expected, $result);
  }

  function testLimitHit() {
    require_once('phpwhois.to.whoapi.php');
    $raw = file_get_contents("it/limit.txt");
    $whois = new PhpwhoisToWhoapi("it", "www.nic.it");
    $result = $whois->convertToWhoapi($raw);

    $expected = array(
      "status" => "success",        // put 'error' on any unexpected problem & describe it below
      "status_desc" => "Request successful",
      "whois_server" => "",       // domain or address where we got the data from
      "limit_hit" => true,        // set to 'true' as we hit the limit
      "registered" => null,
      "disclaimer" => "",       // terms of use whois server has set
      "whois_raw" => "",        // original unparsed whois result
    );

    $this->assertEquals($expected, $result);
  }
}