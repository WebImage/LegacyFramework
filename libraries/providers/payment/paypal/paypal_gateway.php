<?php

/**
 * REQUIRED PAYMENT OPTION PARAMETERS
 * account_email = the PayPal account email that will be used
 */
FrameworkManager::loadLibrary('store');
class PayPalPaymentGateway extends PaymentGateway {
	function charge(&$message) {
		FrameworkManager::loadLogic('order');
		
		$payment_option = $this->getPaymentOption();
		
		// Paypal account
		$account_email = $payment_option->getParam('account_email');
		
		if (empty($account_email)) {
			$message = 'A required payment option parameter was missing: account_email.  This is an internal problem.  Please contact support.';
			return false;
		}

		$order = $payment_option->getOrder();
		$order_struct = $order->getOrderStruct();
		
		
		
		// Mark order as incomplete until it comes back from uPay
		$order_struct->order_status_id = OrderLogic::getOrderStatusIdByKey('incomplete');
		$order->setOrderStruct($order_struct);
		
		$order->complete();
		
		Membership::setParameter('PAYPAL_ORDER_ID', $order->getId());
		
		// Forward to uPay for payment
		Page::redirect(ConfigurationManager::get('DIR_WS_HOME') . 'checkout/gotopaypal.html');
		
		// Not necessary, but what the heck
		return false;
	}
}

?>