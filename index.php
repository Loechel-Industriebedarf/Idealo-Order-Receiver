<?php

	
	require_once "config.php";
	
	//Debug Stuff.
	echo "<h1>USERDATA</h1>";
	echo "<pre>";
	echo "Sandbox? "; var_dump($sandbox);
	echo "Username "; var_dump($username);
	echo "Password "; var_dump($password);
	echo "Shopid "; var_dump($shopid);
	echo "</pre>";
	
	//Check, if csv already exists. If there is already an csv, we don't need to do anything yet
	if(!file_exists($csvPath)){
		$accessToken = getAccessToken($url, $username, $password);
	
		echo "<h1>Access Token</h1>";
		echo "<pre>";
		var_dump($accessToken);
		echo "</pre>";
		
		
		$orders = getNonAcknowledgedOrders($url, $shopid, $accessToken);
		
		echo "<h1>AcknowledgedOrders</h1>";
		echo "<pre>";
		var_dump($orders);
		echo "</pre>";
		
		
		$orderNumbers = extractOrderNumbers($orders);
		
		echo "<h1>Order Numbers</h1>";
		echo "<pre>";
		var_dump($orderNumbers);
		echo "</pre>";
		
		
		$result = setMerchantOrderNumbers($orderNumbers, $url, $shopid, $accessToken);
		
		echo "<h1>Result</h1>";
		echo "<pre>";
		var_dump($result);
		echo "</pre>";
		
		
		//TODO check result; If result == good => write to csv
		echo "<h1>CSV</h1>";
		writeOrdersToCsv($orders);
	}
	else{
		echo "CSV file was not processed yet!";
	}
	
	
	
	
	/*
	* Get Token
	*
	* @param string url				Sandbox or "real" api url
	* @param string username		API username
	* @param string password		API secret
	* @return string				Returns the access token as string
	*/
	function getAccessToken($url, $username, $password){
		$ch = curl_init($url . "/api/v2/oauth/token");
		curl_setopt($ch, CURLOPT_URL, $url . "/api/v2/oauth/token");
		curl_setopt($ch, CURLOPT_POST, 1);
		curl_setopt($ch, CURLOPT_USERPWD, $username . ":" . $password);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		$result = curl_exec ($ch);
		
		curl_close($ch);		
		
		return json_decode($result, true)["access_token"];	
	}
	
	
	
	/*
	* Get orders
	*
	* @param string url				Sandbox or "real" api url
	* @param int shopid				Id of the idealo shop
	* @param string accessToken		Access token generated via getAccessToken()
	* @return array					Returns an array with all non acknowleded orders
	*/
	
	function getNonAcknowledgedOrders($url, $shopid, $accessToken){
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, $url . "/api/v2/shops/" . $shopid . "/orders".
			"?status=PROCESSING" .
			"&acknowledged=false" .
			"&pageNumber=0" .
			"&pageSize=100"
		);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_HTTPHEADER, array(
		   'Content-Type: application/json',
		   'Authorization: Bearer ' . $accessToken
	   ));
		$result = curl_exec($ch);
		
		$json_decode = json_decode($result, true);
		echo "<h1>AcknowledgedOrders JSON DECODE DUMP</h1><pre>";
		var_dump($json_decode);
		echo "</pre>";
		
		curl_close($ch);
		
		
		return json_decode($result, true);	
	}
	
	
	
	/*
	* Extract order numbers from array
	*
	* @param array orders			Array with multiple orders (generated via getNonAcknowledgedOrders() for example)
	* @return array[string]			Returns an array with just the order numbers (without additional info)
	*/
	function extractOrderNumbers($orders){
		return ["12345", "67890"];
	}
	
	
	
	/*
	* Set Merchant Order Number
	*
	* @param array idealoOrderIds	List of order ids, the merchant number should be set for
	* @param string idealoOrderId	One single order id, the merchant number should be set for
	* @param string url				Sandbox or "real" api url
	* @param int shopid				Id of the idealo shop
	* @param string accessToken		Access token generated via getAccessToken()
	* @return array					Returns an array, that tells, if the api call was successfull
	*/
	//Set multiple numbers
	function setMerchantOrderNumbers($idealoOrderIds, $url, $shopid, $accessToken){
		foreach ($idealoOrderIds as &$value) {
			setMerchantOrderNumber($value, $url, $shopid, $accessToken);
		}		
	}
	//Set one number at a time
	function setMerchantOrderNumber($idealoOrderId, $url, $shopid, $accessToken){
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, $url . "/api/v2/shops/ " . $shopid . "/orders/" . $idealoOrderId . "/merchant-order-number");
		curl_setopt($ch, CURLOPT_POST, 1);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_HTTPHEADER, array(
		   'Content-Type: application/json',
		   'Authorization: Bearer ' . $accessToken
		));
	   
	   
		$result = curl_exec ($ch);
		
		return json_decode($result, true);
	}	
	
	
	
	/*
	* Write orders to csv
	*
	* @param array orders			All orders (including order information) that should be written to csv
	*/
	function writeOrdersToCsv($orders){		
		//TODO: Do something useful
		
		echo "csv function";
	}