<?php
require_once('whois.main.php');

class PhpwhoisQuery extends Whois
{
  /**
   * Returns the result of whois query as a String
   *
   * @return string
   */
  function fetch($domain) {
    $query_params = $this->WhoisQueryParams($domain);
    $result = $this->FetchRawData($query_params);
    return is_array($result)?implode("\n", $result):$result;
  }

  /**
   * For given domain name in $query
   * Finds the whois server and the parameters
   */
  function WhoisQueryParams($query = '', $is_utf = true)
  {
    // start clean
    $query_params = array(
      'status' => ''
    );
    
    $query = trim($query);
    
    $IDN = new idna_convert();
    
    if ($is_utf)
      $query = $IDN->encode($query);
    else
      $query = $IDN->encode(utf8_encode($query));
    
    // If domain to query was not set
    if (!isSet($query) || $query == '') {
      // Configure to use default whois server
      $query_params['server'] = $this->NSI_REGISTRY;
      return;
    }
    
    // Set domain to query in query array
    
    $query_params['query'] = $domain = strtolower($query);
    
    // If query is an ip address do ip lookup
    
    if ($query == long2ip(ip2long($query))) {
      // IPv4 Prepare to do lookup via the 'ip' handler
      $ip = @gethostbyname($query);
      
      if (isset($this->WHOIS_SPECIAL['ip'])) {
        $query_params['server'] = $this->WHOIS_SPECIAL['ip'];
        $query_params['args']   = $ip;
      } else {
        $query_params['server']  = 'whois.arin.net';
        $query_params['args']    = "n $ip";
        $query_params['file']    = 'whois.ip.php';
        $query_params['handler'] = 'ip';
      }
      $query_params['host_ip']   = $ip;
      $query_params['query']     = $ip;
      $query_params['tld']       = 'ip';
      $query_params['host_name'] = @gethostbyaddr($ip);
      return $query_params;
    }
    
    if (strpos($query, ':')) {
      // IPv6 AS Prepare to do lookup via the 'ip' handler
      $ip = @gethostbyname($query);
      
      if (isset($this->WHOIS_SPECIAL['ip'])) {
        $query_params['server'] = $this->WHOIS_SPECIAL['ip'];
      } else {
        $query_params['server']  = 'whois.ripe.net';
        $query_params['file']    = 'whois.ip.ripe.php';
        $query_params['handler'] = 'ripe';
      }
      $query_params['query'] = $ip;
      $query_params['tld']   = 'ip';
      return $query_params;
    }
    
    if (!strpos($query, '.')) {
      // AS Prepare to do lookup via the 'ip' handler
      $ip                     = @gethostbyname($query);
      $query_params['server'] = 'whois.arin.net';
      if (strtolower(substr($ip, 0, 2)) == 'as')
        $as = substr($ip, 2);
      else
        $as = $ip;
      $query_params['args']    = "a $as";
      $query_params['file']    = 'whois.ip.php';
      $query_params['handler'] = 'ip';
      $query_params['query']   = $ip;
      $query_params['tld']     = 'as';
      return $query_params;
    }
    
    // Build array of all possible tld's for that domain
    
    $tld      = '';
    $server   = '';
    $dp       = explode('.', $domain);
    $np       = count($dp) - 1;
    $tldtests = array();
    
    for ($i = 0; $i < $np; $i++) {
      array_shift($dp);
      $tldtests[] = implode('.', $dp);
    }
    
    // Search the correct whois server
    
    if ($this->non_icann)
      $special_tlds = array_merge($this->WHOIS_SPECIAL, $this->WHOIS_NON_ICANN);
    else
      $special_tlds = $this->WHOIS_SPECIAL;
    
    foreach ($tldtests as $tld) {
      // Test if we know in advance that no whois server is
      // available for this domain and that we can get the
      // data via http or whois request
      
      if (isset($special_tlds[$tld])) {
        $val = $special_tlds[$tld];
        
        if ($val == '')
          return $this->Unknown();
        
        $domain = substr($query, 0, -strlen($tld) - 1);
        $val    = str_replace('{domain}', $domain, $val);
        $server = str_replace('{tld}', $tld, $val);
        break;
      }
    }
    
    if ($server == '')
      foreach ($tldtests as $tld) {
        // Determine the top level domain, and it's whois server using
        // DNS lookups on 'whois-servers.net'.
        // Assumes a valid DNS response indicates a recognised tld (!?)
        
        $cname = $tld . '.whois-servers.net';
        
        if (gethostbyname($cname) == $cname)
          continue;
        $server = $tld . '.whois-servers.net';
        break;
      }
    
    if ($tld && $server) {
      // If found, set tld and whois server in query array
      $query_params['server'] = $server;
      $query_params['tld']    = $tld;
      $handler                = '';
      
      foreach ($tldtests as $htld) {
        // special handler exists for the tld ?
        
        if (isSet($this->DATA[$htld])) {
          $handler = $this->DATA[$htld];
          break;
        }
        
        // Regular handler exists for the tld ?
        if (($fp = @fopen('whois.' . $htld . '.php', 'r', 1)) and fclose($fp)) {
          $handler = $htld;
          break;
        }
      }
      
      // If there is a handler set it
      
      if ($handler != '') {
        $query_params['file']    = "whois.$handler.php";
        $query_params['handler'] = $handler;
      }
      
      // Special parameters ?
      
      if (isset($this->WHOIS_PARAM[$server]))
        $query_params['server'] = $query_params['server'] . '?' . str_replace('$', $domain, $this->WHOIS_PARAM[$server]);
      
      return $query_params;
    }
  }
  
  /**
   * Queries the whois server and returns the data according to the query_params
   *
   * @return Array
   */
  function FetchRawData(&$query_params)
  {
    $query = $query_params['query'];
    // clear error description
    if (isset($query_params['errstr']))
      unset($query_params['errstr']);
    
    if (!isset($query_params['server'])) {
      $query_params['status']   = 'error';
      $query_params['errstr'][] = 'No server specified';
      return (array());
    }
    
    // Check if protocol is http
    
    if (substr($query_params['server'], 0, 7) == 'http://' || substr($query_params['server'], 0, 8) == 'https://') {
      $output = $this->httpQuery($query_params['server']);
      
      if (!$output) {
        $query_params['status']   = 'error';
        $query_params['errstr'][] = 'Connect failed to: ' . $query_params['server'];
        return (array());
      }
      
      $query_params['args']   = substr(strchr($query_params['server'], '?'), 1);
      $query_params['server'] = strtok($query_params['server'], '?');
      
      if (substr($query_params['server'], 0, 7) == 'http://')
        $query_params['server_port'] = 80;
      else
        $query_params['server_port'] = 483;
    } else {
      // Get args
      if (strpos($query_params['server'], '?')) {
        $parts                  = explode('?', $query_params['server']);
        $query_params['server'] = trim($parts[0]);
        $query_args             = trim($parts[1]);
        
        // replace substitution parameters      
        $query_args = str_replace('{query}', $query, $query_args);
        $query_args = str_replace('{version}', 'phpWhois' . $this->CODE_VERSION, $query_args);
        
        if (strpos($query_args, '{ip}') !== false) {
          $query_args = str_replace('{ip}', phpwhois_getclientip(), $query_args);
        }
        
        if (strpos($query_args, '{hname}') !== false) {
          $query_args = str_replace('{hname}', gethostbyaddr(phpwhois_getclientip()), $query_args);
        }
      } else {
        if (empty($query_params['args']))
          $query_args = $query;
        else
          $query_args = $query_params['args'];
      }
      
      $query_params['args'] = $query_args;
      
      if (substr($query_params['server'], 0, 9) == 'rwhois://') {
        $query_params['server'] = substr($query_params['server'], 9);
      }
      
      if (substr($query_params['server'], 0, 8) == 'whois://') {
        $query_params['server'] = substr($query_params['server'], 8);
      }
      
      // Get port
      
      if (strpos($query_params['server'], ':')) {
        $parts                       = explode(':', $query_params['server']);
        $query_params['server']      = trim($parts[0]);
        $query_params['server_port'] = trim($parts[1]);
      } else
        $query_params['server_port'] = $this->PORT;
      
      // Connect to whois server, or return if failed
      $ptr = $this->Connect2($query_params);
      
      if ($ptr < 0) {
        $query_params['status']   = 'error';
        $query_params['errstr'][] = 'Connect failed to: ' . $query_params['server'];
        return array();
      }
      
      stream_set_timeout($ptr, $this->STIMEOUT);
      stream_set_blocking($ptr, 0);
      
      // Send query
      fputs($ptr, trim($query_args) . "\r\n");
      
      // Prepare to receive result
      $raw   = '';
      $start = time();
      $null  = NULL;
      $r     = array(
        $ptr
      );
      
      while (!feof($ptr)) {
        if (stream_select($r, $null, $null, $this->STIMEOUT)) {
          $raw .= fgets($ptr, $this->BUFFER);
        }

        if (time() - $start > $this->STIMEOUT) {
          $query_params['status']   = 'error';
          $query_params['errstr'][] = 'Timeout reading from ' . $query_params['server'];
          return array();
        }
      }
      
      if (array_key_exists($query_params['server'], $this->NON_UTF8)) {
        $raw = utf8_encode($raw);
      }
      
      $output = explode("\n", $raw);
      
      // Drop empty last line (if it's empty! - saleck)
      if (empty($output[count($output) - 1]))
        unset($output[count($output) - 1]);
    }
    
    return $output;
  }

  /**
   * Opens up a socket to whois server with given params
   */
  function Connect2(&$query_params)
  {
    if ($server == '')
      $server = $query_params['server'];
    
    // Fail if server not set
    if ($server == '')
      return (-1);
    
    // Get rid of protocol and/or get port
    $port = $query_params['server_port'];
    
    $pos = strpos($server, '://');
    
    if ($pos !== false)
      $server = substr($server, $pos + 3);
    
    $pos = strpos($server, ':');
    
    if ($pos !== false) {
      $port   = substr($server, $pos + 1);
      $server = substr($server, 0, $pos);
    }
    
    // Enter connection attempt loop
    $retry = 0;
    
    while ($retry <= $this->RETRY) {
      // Set query status
      $query_params['status'] = 'ready';
      
      // Connect to whois port
      $ptr = @fsockopen($server, $port, $errno, $errstr, $this->STIMEOUT);
      
      if ($ptr > 0) {
        $query_params['status'] = 'ok';
        return ($ptr);
      }
      
      // Failed this attempt
      $query_params['status']  = 'error';
      $query_params['error'][] = $errstr;
      $retry++;
      
      // Sleep before retrying
      sleep($this->SLEEP);
    }
    
    // If we get this far, it hasn't worked
    return (-1);
  }
}