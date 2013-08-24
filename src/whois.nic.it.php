<?php

/*
// Your credidentials:
// Full name
// email for contact
*/

// put the server you're parsing here
// also name this file with that name too
$server = "whois.nic.it";

$raw_whois_parser = function($server, $domain){
	
	// whois request - get raw data
	
	$data = getPort43Request($server, $domain);
	
	/////////////////////////////////// PARSER CODE START
	
	// YOUR PARSER CODE GOES HERE > $data

	/////////////////////////////////// PARSER CODE END
	
	// return parsed array
	// check expected output here first: http://tasks.whoapi.com/public_task_2/
	return $data;
	
};

// for debugging
// comment these commands when done with scripting
//////////////////////////////////////////////////
// domain to parse, put any for debugging
$domain = "unicredit.it";
// make request
$data = $raw_whois_parser($server, $domain);
// echo result
echo "<pre>";
print_r($data);




	//////////////////////////
	// DO NOT UPDATE THIS FILE
	//
	// If in need to create a new fuction to get a whois result 
	// in a different way than already resolved below, then
	// write a new function above the one in the template you're working on

	// dedicated function just for Croatian Whois SOAP api
    function getWhoisHR($domain){
        // call SOAP API
		try                     {   $carnetapi = new SoapClient('https://registrar.carnet.hr/wsdl'); }
		catch (Exception $e)    {   callError(30);  }
		
		// get whois
		$results = $carnetapi->whois("$domain");

        return $results;
    }

	// for parsing web content
    function getHttpRequest($server, $domain){
        $results = @rtrim( @file_get_contents("$server$domain") );
		if ($results === false)	$results = "Error";
		return $results;
    }

	// for parsing whois raw data
    function getPort43Request($server, $domain){
        //  make a port 43 request to the whois sever
        $fp = @fsockopen($server, 43);
		if ($fp === false) return "Error";
        
        //  make the request
		if      ($server == "whois.crsnic.net") fputs($fp, "domain $domain\r\n"); // avoids subdomains with same name as the domain
		else                                    fputs($fp, "$domain\r\n");
        
        //  save the output
		$raw = "";                                  //  default value
		while(!feof($fp)) $raw .= fgets($fp,128);   //  add row by row data

        //  close connection
		fclose($fp);

        return $raw;
    }
