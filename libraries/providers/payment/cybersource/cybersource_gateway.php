<?php

@include('cybersourceextendedclient.php');
/*
Test Environment:		ics2wstest.ic3.com	https://ics2wstest.ic3.com/commerce/1.x/transactionProcessor/CyberSourceTransaction_1.26.wsdl
Production Environment:		ics2ws.ic3.com		https://ics2ws.ic3.com/commerce/1.x/transactionProcessor/CyberSourceTransaction_1.26.wsdl
*/
class CyberSourcePaymentGateway extends PaymentGateway {

	function charge(&$message) {
		
		// Initialize environment
		if (!$this->initEnvironment($message)) {
			$message .= '(CSNOINIT)';
			return false;
		}

		// Setup required SOAP packet
		$request = $this->buildRequest();
		try {
			$soapClient = new CyberSourceExtendedClient(CYBERSOURCE_WSDL_URL, array());
			$reply				= $soapClient->runTransaction($request);
		} catch (SoapFault $exception) {
			$message = 'Your payment was unable to be processed at this time.  Please try your payment again.  If your payment still fails, please contact support with error code CSDECSOAP.';

			return false;
			#var_dump(get_class($exception));
			#var_dump($exception);
		}
		
		// Dump debug data
		if (is_object($reply)) {
			$reply_vars = get_object_vars($reply);
			foreach($reply_vars as $reply_var=>$reply_var_val) {
				if (is_array($reply_var_val)) $reply_var_val = 'ARRAY';
				else if (is_object($reply_var_val)) $reply_var_val = 'OBJECT';
				#FrameworkManager::debug('$reply->' . $reply_var . ' = ' . $reply_var_val);
			}
		}
		
		// Proces transaction response
		switch (strtoupper($reply->decision)) { // $reply->decision should always be upper case, but let's just be sure
			case 'ACCEPT':
				$payment_option = $this->getPaymentOption();
				$order = $payment_option->getOrder();
				$order->complete();
				return true;
				break;
			case 'REJECT':
			case 'ERROR':
				FrameworkManager::log(LOGLEVEL_NOTICE, 'Transaction declined/error: ' . $reply->reasonCode);
				switch ($reply->reasonCode) {
					case 101:
						$message = 'The request is missing one or more required fields. This is an internal problem.  Please contact support with error code CSDEC101.';
						break;
					case 102:
						$message = 'One or more fields in the request contains invalid data. This is an internal problem.  Please contact support with error code CSDEC102.';
						break;
					case 150:
						$message = 'Error: General system failure.  This is an internal problem.  Please contact support with error code CSDEC150.';
						break;
					case 151:
						$message = 'Error: The request was received but there was a server timeout.  This is an internal problem.  Please contact support with error code CSDEC151.';
						break;
					case 152:
						$message = 'Error: The request was received, but a service did not finish running in time.  This is an internal problem.  Please contact support with error code CSDEC152.';
						break;
					case 223:
						$message = 'Error issuing credit.  Generally this occurs when a matching order/transaction cannot be found.  Please contact support with error code CSDEC223.';
						break;
					case 233:
						$message = 'The transaction was declined.  Please verify billing information before continuing.';
						break;
					case 234:
						$message = 'There was an internal error.  Please contact support with error code CSDEC234.';
						break;
					case 236:
						$message = 'There was an internal error.  Please contact support with error code CSDEC236.';
						break;
					case 239:
						$message = 'The requested transaction amount must match the previous transaction amount. Error code: CSDEC239.';
						break;
					case 241:
						$message = 'Invalid request ID.  If you believe this problem to be an error, please contact support with error code CSDEC241.';
						break;
					case 250:
						$message = 'Error: The request was received, but there was a timeout at the payment processor.  If the problem persists, please contact support with error code CSDEC250.';
						break;
					default:
						$message = 'The transaction was declined.  Please double check the payment information you entered.';
						break;
				}
				return false;
				break;
			default:
				$message = 'An unknown process error occurred.  Please contact support.  Error code: CSDECUNKN.';
				return false;
				break;
		}
		// To retrieve individual reply fields, follow these examples.
		#printf( "decision = $reply->decision<br>" );
		#printf( "reasonCode = $reply->reasonCode<br>" );
		#printf( "requestID = $reply->requestID<br>" );
		#printf( "requestToken = $reply->requestToken<br>" );
		#printf( "ccAuthReply->reasonCode = " . $reply->ccAuthReply->reasonCode . "<br>");
	}
	
	/**
	 * Setup CyberSource variables
	 */
	function initEnvironment(&$message) {
		if (!defined('CS_EXTENDED_CLIENT')) {
			$message = 'Unable to process payment.  Please contact support.  Error code: CSXCLIENT.';
			return false; // This would be defined in the cybersourceextendedclient.php include above, without this we cannot process a payment
		}
		
		$payment_option	= $this->getPaymentOption();

		// Get required merchant id
		if (!$merchant_id = $payment_option->getParam('MERCHANT_ID')) {
			$message = 'Payment option is misconfigured.  Please contact support.  Error code: CS001';
			return false;
		}
		
		// Get required transaction key
		if (!$transaction_key_a = $payment_option->getParam('TRANSKEY_A')) {
			$message = 'Payment option is misconfigured.  Please contact support.  Error code: CS002A';
			return false;
		}
		if (!$transaction_key_b = $payment_option->getParam('TRANSKEY_B')) {
			$message = 'Payment option is misconfigured.  Please contact support.  Error code: CS002B';
			return false;
		}
		
		// get required WSDL_URL
		if (!$wsdl_url = $payment_option->getParam('WSDL_URL')) {
			$message = 'Payment option is misconfigured.  Please contact support.  Error code: CS003';
			return false;
		}
		
		/**
		 * Define constants required for CyberSource.  
		 * Constant names were modified to include CYBERSOURCE_ as a sort of name space - 
		 * just to make sure they do not clash with any other defined constants
		 */
		define('CYBERSOURCE_MERCHANT_ID',	$merchant_id);
		define('CYBERSOURCE_TRANSACTION_KEY',	$transaction_key_a . $transaction_key_b);
		define('CYBERSOURCE_WSDL_URL',		$wsdl_url);
		
		FrameworkManager::debug('CYBERSOURCE_WSDL_URL = ' . CYBERSOURCE_WSDL_URL);
		
		return true;
	}
	
	/**
	 * Build the SOAP request required by CyberSource
	 */
	function buildRequest() {
		$payment_option = $this->getPaymentOption();
		$order = $payment_option->getOrder();
		$order_struct = $order->getOrderStruct();
		$order_total = $order->getTotal();
		$order_items = $order->getOrderItems();
		$payment = $payment_option->getPayment();

		$billing_first_name = '';
		$billing_last_name = '';
		$billing_name_parts = explode(' ', $payment->billing_name, 2);
		$billing_first_name = $billing_name_parts[0];
		if(isset($billing_name_parts[1])) $billing_last_name = $billing_name_parts[1];

			
			#To see the functions and types that the SOAP extension can automatically
			#generate from the WSDL file, uncomment this section.
			#$functions = $soapClient->__getFunctions();
			#print_r($functions);
			#$types = $soapClient->__getTypes();
			#print_r($types);
		
			$request = new stdClass();
		
			$request->merchantID = CYBERSOURCE_MERCHANT_ID;
		
			// Before using this example, replace the generic value with you own.
			$request->merchantReferenceCode = date('YmdHis'); //"your_merchant_reference_code";
		
			// To help us troubleshoot any problems that you may encounter,
			// please include the following information about your PHP application.
			$request->clientLibrary = "PHP";
					$request->clientLibraryVersion = phpversion();
					$request->clientEnvironment = php_uname();
		
			// This section contains a sample transaction request for the authorization
			// service with complete billing, payment card, and purchase (two items) information.
			$ccAuthService = new stdClass();
			$ccAuthService->run = "true";
			$request->ccAuthService = $ccAuthService;
			
			$ccCaptureService = new stdClass();
			$ccCaptureService->run = "true";
			$request->ccCaptureService = $ccCaptureService;

			$billTo				= new stdClass();
			$billTo->firstName		= $billing_first_name;
			$billTo->lastName		= $billing_last_name;
			$billTo->street1		= $payment->billing_address1;
			$billTo->city			= $payment->billing_city;
			$billTo->state			= $payment->billing_state_abbrev;
			$billTo->postalCode		= $payment->billing_zip;
			$billTo->country		= $payment->billing_country_iso_code_2;
			$billTo->email			= $order_struct->email;
			$billTo->ipAddress		= $_SERVER['REMOTE_ADDR'];
			$request->billTo		= $billTo;
			
			$card				= new stdClass();
			$card->accountNumber		= $payment->credit_card_number;
			$card->expirationMonth		= substr($payment->credit_card_expiration, 0, 2);
			$card->expirationYear		= substr($payment->credit_card_expiration, 4, 2);
			$card->cvNumber			= $payment->credit_card_csc;
			$request->card			= $card;

			$purchaseTotals			= new stdClass();
			$purchaseTotals->currency	= "USD";
			$purchaseTotals->grandTotalAmount=$order_total;
			$request->purchaseTotals	= $purchaseTotals;

			$request->item			= array();

			foreach($order_items as $order_item) {
				$order_item_struct = $order_item['order_item_struct'];
				$item = new stdClass();
				$item->unitPrice = $order_item_struct->price - $order_item_struct->discount;
				$item->quantity = $order_item_struct->quantity;
				$item->id = $order_item_struct->sku_id;
				array_push($request->item, $item);
			}

			return $request;
		// This section will show all the reply fields.
		// var_dump($reply);
		
		// To retrieve individual reply fields, follow these examples.
		

		return false;
	}
	
}

?>