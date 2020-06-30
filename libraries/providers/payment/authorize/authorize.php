<?php

/*
// Declared in authorize_options.php
class AuthorizePaymentOption extends PaymentOption {
	
	function validateForm() {
		$payment_option = $this->getPaymentOption();
		
		$card_csc	= Page::get('auth_card_csc');
		$card_type	= Page::get('auth_card_type');
		$card_number	= Page::get('auth_card_number');
		$card_exp_month	= Page::get('auth_card_exp_month');
		$card_exp_year	= Page::get('auth_card_exp_year');
		
		if (empty($card_number)) 		$this->addError('Card number is required');
		if (empty($card_type))			$this->addError('Card type is required.');
		if (empty($card_exp_month))		$this->addError('Card expiration month is required');
		if (empty($card_exp_year))		$this->addError('Card expiration year is required.');

		if (!empty($card_exp_month) && !empty($card_exp_year)) {
			$days_in_month = date('t', mktime(0, 0, 0, $card_exp_month, 1, $card_exp_year));
			$cc_time = mktime(0, 0, 0, $card_exp_month, $days_in_month, $card_exp_year);
			if ($cc_time < time()) {
				$this->addError('The credit card expiration date occurs in the past.');
			}
		}

		if ($this->anyErrors()) return false;
		else {
			$this->_order->setCreditCardCsc($card_csc);
			$this->_order->setCreditCardExpiration($card_exp_month . '/' . $card_exp_year);
			$this->_order->setCreditCardName($card_name);
			$this->_order->setCreditCardNumber($card_number);
			$this->_order->setCreditCardType($card_type);
			$this->_order->setPaymentType($payment_option->id);
			return true;
		}
	}
	
}
*/
?>