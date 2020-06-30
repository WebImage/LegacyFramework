<?php

class CyberSourcePaymentOption extends PaymentOption {
	
	function bindPaymentToForm() {
		$payment = $this->getPayment();
		
		// Field Control Objects
		$txt_card_number	= Page::getControlById($this->getFormFieldName('credit_card_number'));
		$cbo_card_type		= Page::getControlById($this->getFormFieldName('credit_card_type'));
		$cbo_card_exp_month	= Page::getControlById($this->getFormFieldName('credit_card_exp_month'));
		$cbo_card_exp_year	= Page::getControlById($this->getFormFieldName('credit_card_exp_year'));
		$txt_card_csc		= Page::getControlById($this->getFormFieldName('credit_card_csc'));
		
		$cbo_card_type->setData( $this->_getCardTypes() );
		$cbo_card_exp_month->setData( $this->_getCardExpMonths() );
		$cbo_card_exp_year->setData( $this->_getCardExpYears() );
		
		$txt_card_number->setValue( $payment->credit_card_number );
		$cbo_card_type->setValue( $payment->credit_card_type );
		$cbo_card_exp_month->setValue( substr($payment->credit_card_expiration, 0, 2) );
		$cbo_card_exp_year->setValue( substr($payment->credit_card_expiration, 2, 4) );
		$txt_card_csc->setValue( $payment->credit_card_csc );
		

	}
	function bindFormToPayment() {
		$payment = $this->getPayment();
		
		$card_exp_month	= Page::get($this->getFormFieldName('credit_card_exp_month'));
		$card_exp_year	= Page::get($this->getFormFieldName('credit_card_exp_year'));
		
		$payment->credit_card_expiration	= $card_exp_month . $card_exp_year;
		$payment->credit_card_number		= Page::get($this->getFormFieldName('credit_card_number'));
		$payment->credit_card_type		= Page::get($this->getFormFieldName('credit_card_type'));
		$payment->credit_card_csc		= Page::get($this->getFormFieldName('credit_card_csc'));
		
		$this->setPayment($payment);
	}
	
	function validatePaymentStruct() {
		$payment = $this->getPayment();

		if (empty($payment->credit_card_number)) 		$this->addError('Card number is required');
		#else if (!$this->_validateCreditCardNumber($payment->credit_card_number)) $this->addError("The credit number entered is invalid.");

		if (empty($payment->credit_card_type))			$this->addError('Card type is required.');
		if (strlen($payment->credit_card_expiration) != 6)	$this->addError('Card expiration month and year are required');
		else if (strlen($payment->credit_card_expiration) == 6) { // MMYYYY
			$card_exp_month = substr($payment->credit_card_expiration, 0, 2);
			$card_exp_year = substr($payment->credit_card_expiration, 2, 4);
			
			$days_in_month = date('t', mktime(0, 0, 0, $card_exp_month, 1, $card_exp_year));
			$cc_time = mktime(23, 59, 59, $card_exp_month, $days_in_month, $card_exp_year);

			if ($cc_time < time()) {
				$this->addError('The credit card expiration date occurs in the past.');
			}
		}
		
		if (empty($payment->credit_card_csc))			$this->addError('Card verification number is required.  This is the 3-to-4 digit code on the back or front of your card.');
		else {
			if (preg_match('/[^0-9]+/', $payment->credit_card_csc)) $this->addError('Please enter a valid credit card verification number.  This is the 3-to-4 digit code on the back or front of your card.');
		}
		
		return !$this->anyErrors();
		
	}
	
	/**
	 * Build a ResultSet of months
	 * @access private
	 */
	function _getCardExpMonths() {
		$result_months		= new ResultSet();
		$months			= array('January', 'February', 'March', 'April', 'May', 'June', 'July', 'August', 'September', 'October', 'November', 'December');
		
		for ($m=1; $m <= 12; $m++) {
			$tmp_month	= new stdClass();
			$tmp_month->id	= sprintf("%02d", $m);
			$tmp_month->name= sprintf("%02d", $m);
			$result_months->add($tmp_month);
		}
		return $result_months;
	}
	/**
	 * Build a ResultSet of years
	 */
	function _getCardExpYears() {
		$result_years		= new ResultSet();
		$year_start		= date('Y');
		$year_end		= $year_start + 10;
		
		for ($y=$year_start; $y <= $year_end; $y++) {
			$tmp_year	= new stdClass();
			$tmp_year->id	= $y;
			$tmp_year->name	= $y;
			$result_years->add($tmp_year);
		}
		return $result_years;
	}
	/**
	 * Build ResultSet of card types
	 */
	function _getCardTypes() {
		$config_card_types	= 'MC=>Master Card;V=>Visa;AMX=>American Express;D=>Discover;DC=>Diners Club;';

		$config_cards = explode(';', $config_card_types);
		
		$result_card_types = new ResultSet();
		
		foreach($config_cards as $config_card) {
		
			$id_name	= explode('=>', $config_card);
			$tmp_id		= $id_name[0];
			
			if (isset($id_name[1])) $tmp_name = $id_name[1];	
			else $tmp_name = $id_name[0];
			if (strlen($tmp_id) > 0 || strlen($tmp_name) > 0) {
				$tmp_card	= new stdClass();
				$tmp_card->id	= $tmp_id;
				$tmp_card->name	= $tmp_name;
				$result_card_types->add($tmp_card);
			}
		}
		return $result_card_types;
	}
	/**
	 * Validate the credit card number using Luhn's algorithm
	 */
	function _validateCreditCardNumber($number) {
		$sum = 0;
		$digit = 0;
		$append = 0;
		$times_two = false;
		
		for ($i = strlen($number)-1; $i >= 0; $i--) {
	
			$digit = intval(substr($number, $i, 1));
			if ($times_two) {
				$addend = $digit * 2;
				if ($addend > 9) {
					$addend -= 9;
				}
			} else {
				$addend = $digit;
			}
			$sum += $addend;
				$times_two = !$times_two;
			}
			$modulus = $sum % 10;
			return ($modulus == 0);
	}	


}

?>