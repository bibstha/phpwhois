<?php

/*
// Your credidentials:
// Full name
// email for contact
*/


$data = getPort43Request($server, $domain);

// put the server you're parsing here
// also name this file with that name too
$server = "whois.nic.co";

$raw_whois_parser = function($server, $domain){
	
	// whois request - get raw data
	include("whois_functions.php");
	
	
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
$domain = "domain.co";
// make request
$data = $raw_whois_parser($server, $domain);
// echo result
echo "<pre>";
print_r($data);