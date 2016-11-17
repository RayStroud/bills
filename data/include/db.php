<?php
	//* DEBUG */ error_reporting(E_ALL);
	//* DEBUG */ ini_set('display_errors', 1);
	/* NON-DEBUG */ error_reporting(0);	//to suppress errors
	$dbhost = 'localhost';
	$dbname = 'bills';
	$dbuser = 'root';
	$dbpass = '';			//WAMP
	//$dbpass = 'root'; 	//MAMP
	try 
	{
		$db = new mysqli($dbhost, $dbuser, $dbpass, $dbname);
		if ($db->connect_error) 
		{
			http_response_code(500);
			die('Connect Error (' . $db->connect_errno . ') '. $db->connect_error);
		}
		//* DEBUG */ else {echo 'php version: ' . phpversion() . '<br/>mysql version: ' . $db->get_server_info() . '<hr>';}

		//allow access from domain
		$safeDomains = array('http://raystroud.ca', 'https://raystroud.ca', 'http://www.raystroud.ca', 'https://www.raystroud.ca', 'http://raystroud.com', 'https://raystroud.com', 'http://www.raystroud.com', 'https://www.raystroud.com');
		$domain = $_SERVER['HTTP_ORIGIN'];
		if (in_array($domain, $safeDomains, true))
		{
			header('Access-Control-Allow-Origin: ' . $domain);
			header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
			header('Access-Control-Allow-Headers: Origin, X-Requested-With, Content-Type, Accept, Authorization');
		}
		//* DEBUG */ header('Access-Control-Allow-Origin: *');
	} 
	catch (Exception $e) 
	{
		http_response_code(500);
		die();
	}
?>