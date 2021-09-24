<?php
	$username = "d730802c-2a09-4db6-8bfd-2038f476aca4";
	$password = "pass";
	$shopnumber = "12345";
	$sandbox = true;
	$csvPath = "../idealoOrder.csv";
	
	$url = "https://orders.idealo.com";
	if($sandbox){
		$url = "https://orders-sandbox.idealo.com";
	}
	