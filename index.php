<?php
	//Load configuration file
	require_once "config.php";
	
	//Debug Stuff.
	/*
	echo "<h1>USERDATA</h1>";
	echo "<pre>";
	echo "Sandbox? "; var_dump($sandbox);
	echo "Username "; var_dump($username);
	echo "Password "; var_dump($password);
	echo "Shopid "; var_dump($shopid);
	echo "</pre>";
	*/
	
	//Check, if csv already exists. If there is already an csv, we don't need to do anything yet
	if(!file_exists($csvPath)){
		$accessToken = getAccessToken($url, $username, $password);
	
		//Debug Stuff.
		/*
		echo "<h1>Access Token</h1>";
		echo "<pre>";
		var_dump($accessToken);
		echo "</pre>";
		*/
		
		
		$orders = getNonAcknowledgedOrders($url, $shopid, $accessToken);
		
		//Debug Stuff.
		/*
		echo "<h1>NonAcknowledgedOrders</h1>";
		echo "<pre>";
		var_dump($orders);
		echo "</pre>";
		*/
		
		//If there are new orders, do something with them
		if($orders["totalElements"] > 0){
			$orderNumbers = extractOrderNumbers($orders);
		
			//Debug Stuff.
			/*
			echo "<h1>Order Numbers</h1>";
			echo "<pre>";
			var_dump($orderNumbers);
			echo "</pre>";
			*/
			
			
			
			$result = setMerchantOrderNumbers($orderNumbers, $url, $shopid, $accessToken);

			
			//Debug Stuff.
			/*
			echo "<h1>Result of setMerchantOrderNumbers</h1>";
			echo "<pre>";
			var_dump($result);
			echo "</pre>";
			*/
			
			//$result seems to always be null...
			//if($result !== null){				
				//echo "<h1>CSV</h1>";
				writeOrdersToCsv($orders, $csvPath);
			//}
			
		}
		else{
			echo "No new orders!";
		}
	}
	else{
		echo "CSV file was not processed yet!";
	}
	
	
	
	
	/*
	* Get Token
	*
	* @param string url				Sandbox or production api url
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
	* @param string url				Sandbox or production api url
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
		
		curl_close($ch);
		
		
		return json_decode($result, true);	
	}
	
	
	
	/*
	* Extract order numbers from array
	*
	* @param array orders			Array with multiple orders (generated via getNonAcknowledgedOrders() for example)
	* @return array[string]			Returns an array with just the idealo order numbers (without additional info)
	*/
	function extractOrderNumbers($orders){
		$orderNumbers = array();
		foreach($orders["content"] as $orderContent){			
			array_push($orderNumbers, $orderContent["idealoOrderId"]);
		}
		return $orderNumbers;
	}
	
	
	
	/*
	* Set Merchant Order Number
	*
	* @param array idealoOrderIds	List of order ids, the merchant number should be set for
	* @param string idealoOrderId	One single order id, the merchant number should be set for
	* @param string url				Sandbox or production api url
	* @param int shopid				Id of the idealo shop
	* @param string accessToken		Access token generated via getAccessToken()
	* @return array					Returns an array, that tells, if the api call was successfull
	*/
	//Set multiple numbers
	function setMerchantOrderNumbers($idealoOrderIds, $url, $shopid, $accessToken){
		foreach ($idealoOrderIds as &$value) {
			echo setMerchantOrderNumber($value, $url, $shopid, $accessToken);
		}	
	}
	//Set one number at a time
	function setMerchantOrderNumber($idealoOrderId, $url, $shopid, $accessToken){
		$ch = curl_init();
		$payload = json_encode( array( "merchantOrderNumber"=> $idealoOrderId ) );
		curl_setopt( $ch, CURLOPT_POSTFIELDS, $payload );
		curl_setopt( $ch, CURLOPT_HTTPHEADER, array('Content-Type:application/json'));
		curl_setopt($ch, CURLOPT_URL, $url . "/api/v2/shops/" . $shopid . "/orders/" . $idealoOrderId . "/merchant-order-number");
		curl_setopt($ch, CURLOPT_POST, 1);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_HTTPHEADER, array(
		   'Content-Type: application/json',
		   'Authorization: Bearer ' . $accessToken
		));
		
	   
		$result = curl_exec ($ch);
		//Debug stuff
		/*
		echo "<pre>";
		var_dump($result);
		echo "</pre>";
		*/
		
		return json_decode($result, true);
	}	
	
	
	
	/*
	* Write orders to csv
	*
	* @param array orders			All orders (including order information) that should be written to csv
	* @param string csvpath			Where should the csv be saved?
	*/
	function writeOrdersToCsv($orders, $csvPath){		
		$csv = "";
		
		//Cycle throught all orders
		foreach($orders["content"] as $orderContent){
			//Debug stuff
			
			echo "<pre>";
			echo var_dump($orderContent);
			echo "</pre>";
			
			
			error_reporting(0);
			
			//Cycle throught items in the order
			foreach($orderContent["lineItems"] as $lineItem){	
				$quantity = $lineItem["quantity"];
				$price = $lineItem["price"];
				$fees = floatval($lineItem["price"])/floatval($lineItem["quantity"])*0.09;
				$title = strtolower($lineItem["title"]);
			
				if(strpos($title, 'er pack')){
					$strpostitle = substr($title,0,strpos(strtolower($title),"er pack")); //Cut everything after "er Pack"
					$lastspace = strrpos($strpostitle, ' '); //Search for last space
					if($lastspace > 0){
						$strpostitle = substr($strpostitle, $lastspace, strlen($strpostitle)); //Cut everything before last space
					}	
					$quantity *= intval($strpostitle); //Get "real" quantity
					$price = doubleval($price) / doubleval($strpostitle); //Get "real" price
					$fees = $fees / doubleval($strpostitle) + 0.01; //Get "real" fees
				}
			
				$csv = $csv . $orderContent["idealoOrderId"] . ";"; //OrderNumber
				$csv = $csv . $orderContent["created"] . ";"; //OrderDate
				$csv = $csv . $orderContent["customer"]["email"] . ";"; //EMail
				$csv = $csv . $lineItem["sku"] . ";"; //ArticleNumber
				$csv = $csv . $quantity . ";"; //Quantity
				$csv = $csv . $price . ";"; //ArticlePrice
				$csv = $csv . $orderContent["shippingAddress"]["firstName"] . " " . $orderContent["shippingAddress"]["lastName"] . ";"; //DeliveryClient
				$csv = $csv . $orderContent["shippingAddress"]["addressLine1"] . ";"; //DeliveryStreet
				$csv = $csv . $orderContent["shippingAddress"]["addressLine2"] . ";"; //DeliveryClient2
				$csv = $csv . $orderContent["shippingAddress"]["postalCode"] . ";"; //DeliveryZIP
				$csv = $csv . $orderContent["shippingAddress"]["city"] . ";"; //DeliveryCity
				$csv = $csv . $orderContent["shippingAddress"]["countryCode"] . ";"; //DeliveryCountry
				$csv = $csv . $orderContent["billingAddress"]["firstName"] . " " . $orderContent["billingAddress"]["lastName"] . ";"; //InvoiceClient
				$csv = $csv . $orderContent["billingAddress"]["addressLine1"] . ";"; //InvoiceStreet
				$csv = $csv . $orderContent["billingAddress"]["addressLine2"] . ";"; //InvoiceClient2
				$csv = $csv . $orderContent["billingAddress"]["postalCode"] . ";"; //InvoiceZIP
				$csv = $csv . $orderContent["billingAddress"]["city"] . ";"; //InvoiceCity
				$csv = $csv . $orderContent["billingAddress"]["countryCode"] . ";"; //InvoiceCountry
				$csv = $csv . $orderContent["customer"]["phone"] . ";"; //Phone
				$csv = $csv . $orderContent["payment"]["paymentMethod"] . ";"; //PaymentMethod
				$csv = $csv . $orderContent["payment"]["transactionId"] . ";"; //TransactionId
				$csv = $csv . $orderContent["shippingCosts"] . ";"; //TransactionId
				$csv = $csv . $fees . ";"; //9% Fees
				$csv = $csv . $title . ";"; //Item name
				$csv = $csv . "\r\n";
			}
			error_reporting(-1);
		}
		
		//Debug purposes
		echo $csv;
    
		//Check, if we actually got a result
		if($csv !== ""){
			//Add headline
			$csv = generateCSVHeadline().$csv;
			//Write to file
			$fp = fopen($csvPath, 'w');
			fwrite($fp, $csv);
			fclose($fp);
		}	
	}
	
	
	
	/*
	* Generate csv headline
	*
	* @return string 				Headline for the csv file (including line break)
	*/
	function generateCSVHeadline(){
		$csv_headline = ""
            . "OrderNumber;OrderDate;EMail;"
            . "ArticleNumber;ArticleQuantity;ArticlePrice;"
            . "DeliveryClient;DeliveryStreet;DeliveryClient2;"
            . "DeliveryZIP;DeliveryCity;DeliveryCountry;"
            . "InvoiceClient;InvoiceStreet;InvoiceClient2;"
            . "InvoiceZIP;InvoiceCity;InvoiceCountry;"
            . "Phone;PaymentType;TransactionId;"
			. "Shipping;Fees" 
			. "\r\n";
		return $csv_headline;
	}