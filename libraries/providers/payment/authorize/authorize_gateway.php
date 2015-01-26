<?php

class AuthorizePaymentGateway extends PaymentGateway {

	function charge(&$message) {
		$payment_option	= $this->getPaymentOption();
		$order		= $payment_option->getOrder();
		$order->complete();
		
		return true;
	}
	
}

?>