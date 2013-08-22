<?php

/*
// Your credidentials:
// Full name
// email for contact
*/

require("whois_functions.php");

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