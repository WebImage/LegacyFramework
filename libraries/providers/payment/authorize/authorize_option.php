<?php

FrameworkManager::loadLibrary('store');

class AuthorizePaymentOption extends PaymentOption {
	
	function validateForm() {
		$payment_option = $this->getPaymentOption();
		
		$card_csc	= Page::get($this->getFormFieldName('auth_card_csc'));
		$card_type	= Page::get($this->getFormFieldName('auth_card_type'));
		$card_number	= Page::get($this->getFormFieldName('auth_card_number'));
		$card_exp_month	= Page::get($this->getFormFieldName('auth_card_exp_month'));
		$card_exp_year	= Page::get($this->getFormFieldName('auth_card_exp_year'));

		if (empty($card_number)) 		$this->addError('Card number is required');
		if (empty($card_type))			$this->addError('Card type is required.');
		if (empty($card_exp_month))		$this->addError('Card expiration month is required');
		if (empty($card_exp_year))		$this->addError('Card expiration year is required.');

		if (!empty($card_exp_month) && !empty($card_exp_year)) {
			$days_in_month = date('t', mktime(0, 0, 0, $card_exp_month, 1, $card_exp_year));
			$cc_time = mktime(0, 0, 0, $card_exp_month, $days_in_month, $card_exp_year);
			if ($cc_time < mktime()) {
				$this->addError('The credit card expiration date occurs in the past.');
			}
		}

		if ($this->anyErrors()) return false;
		else {
			$payment_info = $this->getPaymentInfo();

			$payment_info->setCreditCardCsc($card_csc);
			$payment_info->setCreditCardExpiration($card_exp_month . $card_exp_year);
			$payment_info->setCreditCardNumber($card_number);
			$payment_info->setCreditCardType($card_type);
			
			$this->setPaymentInfo($payment_info);
			return true;
		}
	}
	
}

?>