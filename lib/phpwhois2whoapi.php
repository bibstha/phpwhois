<?php
class PhpwhoisToWhoapi {

  function __construct($handlerName, $whoisServer) {
    $this->handlerName = $handlerName;
    $this->whoisServer = $whoisServer;
  }
  
  function convertToWhoapi($rawData) {
    if (!$this->handlerName) {
      return [];
    }

    $handlerClass = $this->handlerName."_handler";
    $fileName = sprintf("whois.%s.php", $this->handlerName);
    require_once($fileName);

    $handler = new $handlerClass();
    $result = $handler->parse(["rawdata" => explode("\n", $rawData)], "");
    return $result;
  }

  function mapPhpwhoisToWhoapi($phpwhoFormat) {
    $ret = [];
    if ("yes" == $phpwhoFormat['regrinfo']) {
      $ret["status"] = "success";
      $ret["status_desc"] = "Request successful",
      $ret["whois_server"] = $this->server
    }
  }
}