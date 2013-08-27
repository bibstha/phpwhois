<?php
class PhpwhoisToWhoapi {

  /**
   * @param $handlerName - it, de, ... 
   *   which relates to respective whois.it.de file
   * @param $whoisServer - 
   * @param $handlerFile - 
   */
  function __construct($handlerName, $whoisServer, $fileName = null) {
    $this->handlerName = $handlerName;
    $this->whoisServer = $whoisServer;
    $this->fileName = $fileName;
  }
  
  function convertToWhoapi($str) {
    if (!$this->handlerName) {
      return [];
    }

    if ((strlen(trim($str)) == 0) || 
      (strpos(strtolower($str), "error") !== false) || 
      (strpos(strtolower($str), "limit") !== false)) {
      return $this->parseError($str);
    }
    else {
      $phpwhoisResult = $this->parseStrToPhpwhois($str);
      // print_r($phpwhoisResult);
      $whoapiResult = $this->mapPhpwhoisToWhoapi($phpwhoisResult);  
    }
    

    return $whoapiResult;
  }

  function parseStrToPhpwhois($str) {
    $handlerClass = $this->handlerName."_handler";
    if ($this->fileName) {
      $fileName = $this->fileName;
    }
    else {
      $fileName = sprintf("whois.%s.php", $this->handlerName);  
    }
    require_once($fileName);

    $handler = new $handlerClass();
    $rawDataArr = ["rawdata" => explode("\n", $str)];
    $result = $handler->parse($rawDataArr, "");
    $result += $rawDataArr;
    return $result;
  }

  function mapPhpwhoisToWhoapi($phpwhoFormat) {
    $ret = [];
    switch (strtolower($phpwhoFormat['regrinfo']['registered'])) {
      case "no":
      $ret += $this->mapNotFound($phpwhoFormat);
      break;

      case "yes":
      $ret += $this->mapBasicDetails($phpwhoFormat);
      $ret["contacts"] = $this->mapContacts($phpwhoFormat);
      // Collect emails
      $ret["emails"] = $this->collectEmails($ret);
      break;

      default:
      break;
    }
    return $ret;
  }

  function mapBasicDetails($phpwhoFormat) {
    $ret["status"] = "success";
    $ret["status_desc"] = "Request successful";
    $ret["whois_server"] = $this->whoisServer;
    $ret["limit_hit"] = false;
    $ret["registered"] = true;
    $ret["premium"] = false;
    $ret["domain_status"] = [];
    $ret["date_created"] = ($phpwhoFormat["regrinfo"]["domain"]["created"])?: '';;
    $ret["date_updated"] = ($phpwhoFormat["regrinfo"]["domain"]["changed"])?: '';
    $ret["date_expires"] = ($phpwhoFormat["regrinfo"]["domain"]["expires"])?: '';;
    // $ret["date_transferred"] = "";
    $ret["contacts"] = [];
    $ret["nameservers"] = ($phpwhoFormat["regrinfo"]["domain"]["nserver"])?: null;
    $ret["disclaimer"] = ($phpwhoFormat["regrinfo"]["disclaimer"])?: null;
    $ret["emails"] = [];
    $ret["whois_raw"] = implode("\n", $phpwhoFormat["rawdata"]);
    return $ret;
  }

  function mapContacts($phpwhoFormat) {
    // print_r($phpwhoFormat);
    $contactTypes = [
      "registrant" => "owner",
      "admin" => "admin",
      "zone" => "zone", 
      "tech" => "tech",
      "billing" => "billing",
      "registrar" => "registrar",
    ];
    $contacts = [];
    foreach ($contactTypes as $key => $contactType) {
      if (!isset($phpwhoFormat["regrinfo"][$contactType])) {
        continue;
      }
      else {
        $srcArray = $phpwhoFormat["regrinfo"][$contactType];
        
        $contact = [];
        $contact["type"] = $key;
        $contact["name"] = $srcArray["name"];
        $contact["full_address"] = "";

        if (isset($srcArray["organization"])) {
          $contact["organization"] = $srcArray["organization"];
          $contact["full_address"] = $contact["organization"] . "\n";
        }
        
        if (isset($srcArray["address"]["street"])) {
          $contact["street"] = implode(", ", $srcArray["address"]["street"]);
          $contact["full_address"] .= $contact["street"] . "\n";
        }
        if (isset($srcArray["address"]["city"])) {
          $contact["city"] = $srcArray["address"]["city"];
          $contact["full_address"] .= $contact["city"] . "\n";
        }
        if (isset($srcArray["address"]["pcode"])) {
          $contact["zipcode"] = $srcArray["address"]["pcode"];
          $contact["full_address"] .= $contact["zipcode"] . "\n";
        }
        if (isset($srcArray["address"]["state"])) {
          $contact["state"] = $srcArray["address"]["state"];
          $contact["full_address"] .= $contact["state"] . "\n";
        }
        if (isset($srcArray["address"]["country"])) {
          $contact["country"] = $srcArray["address"]["country"];
          $contact["full_address"] .= $contact["country"] . "\n";
        }
        if (isset($srcArray["phone"]))
          $contact["phone"] = $srcArray["phone"];
        if (isset($srcArray["fax"]))
          $contact["fax"] = $srcArray["fax"];
        if (isset($srcArray["email"]))
          $contact["email"] = $srcArray["email"];

        $contact["full_address"] = preg_replace("/\n$/", "", $contact["full_address"]);
      }
      $contacts []= $contact;
    }
    return $contacts;
  }

  function collectEmails($whoapiFormat) {
    $res = [];
    if (isset($whoapiFormat["contacts"])) {
      foreach ($whoapiFormat["contacts"] as $contact) {
        if (isset($contact["email"]) && !in_array($contact["email"], $res)) {
          $res []= $contact["email"];
        }
      }
    }
    return $res;
  }

  function mapNotFound($phpwhoFormat) {
    return array(
      "status" => "success",        // put 'error' on any unexpected problem & describe it below
      "status_desc" => "Request successful",
      "whois_server" => $this->whoisServer,         // domain or address where we got the data from
      "limit_hit" => false,         // only 'true' when the whois limit is hit
      "registered" => false,
      "disclaimer" => "",       // terms of use whois server has set
      "whois_raw" => implode("\n", $phpwhoFormat["rawdata"]),  // original unparsed whois result
    );
  }

  function parseError($rawStr) {
    if (strlen($rawStr) === 0 || strpos(strtolower($rawStr), "limit")) {
      return array(
        "status" => "success",        // put 'error' on any unexpected problem & describe it below
        "status_desc" => "Request successful",
        "whois_server" => "",       // domain or address where we got the data from
        "limit_hit" => true,        // set to 'true' as we hit the limit
        "registered" => null,
        "disclaimer" => "",       // terms of use whois server has set
        "whois_raw" => $rawStr,        // original unparsed whois result
      );
    }
    else {
      return [];
    }
  }
}