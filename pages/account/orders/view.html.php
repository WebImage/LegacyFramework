<?php

if (!$user = Membership::getUser()) Page::redirect(ConfigurationManager::get('DIR_WS_HOME') . 'login.html');

FrameworkManager::loadLibrary('store');
FrameworkManager::loadLogic('order');

$o = Page::get('o'); // The date stamped order id YYYYMMDD-ORDERID

// Double check that passed order id is the date stamped order id as defined above
if (!preg_match('/([0-9]{8})-([0-9]+)/', $o, $matches)) Page::redirect(ConfigurationManager::get('DIR_WS_HOME') . 'account/');

$order_date = $matches[1];
$order_id = (int)$matches[2];

// Check that order exists
if (!$order = OrderHelper::getFromOrderId($order_id)) Page::redirect(ConfigurationManager::get('DIR_WS_HOME') . 'account/');
// Check that user owns this order
if ($order->getCustomerId() != $user->getId()) Page::redirect(ConfigurationManager::get('DIR_WS_HOME') . 'account/');

$order_struct = $order->getOrderStruct();

/**
 * Get Order Detail
 */
$created	= database_format_date('F d, Y', $order->getCreated());
$email		= $order->getEmail();

// Status Info
$status_id	= $order->getOrderStatusId();
$status_struct	= OrderLogic::getOrderStatusById($status_id);
$status		= $status_struct->friendly_name;

$shipping_cost	= $order->getShippingCost();
$tax		= $order->getTax();
$total		= $order->getTotal();

/**
 * Set Fields 
 */
$lbl_created = Page::getControlById('lbl_created');
$lbl_created->setText($created);

$lbl_email = Page::getControlById('lbl_email');
$lbl_email->setText($email);

$lbl_status = Page::getControlById('lbl_status');
$lbl_status->setText($status);

$lbl_order_id = Page::getControlById('lbl_order_id');
$lbl_order_id->setText( OrderHelper::getFormattedIdFromStruct($order_struct) );

$lbl_tax = Page::getControlById('lbl_tax');
$lbl_tax->setText(number_format($tax, 2));

$lbl_shipping_cost = Page::getControlById('lbl_shipping_cost');
$lbl_shipping_cost->setText(number_format($shipping_cost, 2));

$lbl_total = Page::getControlById('lbl_total');
$lbl_total->setText(number_format($total, 2));

/**
 * Get items with additional sku options, such as getUserInstructions()
 */
$items = $order->getExtendedOrderSkus();
$rs_items = new ResultSet();

foreach($items as $item) {
	$order_sku = $item->getOrderSkuStruct();
	
	$user_instructions = $item->getUserInstructions();
	if (empty($user_instructions)) 
		$order_sku->user_instructions = '';
	else 
		$order_sku->user_instructions = '<div style="border:1px solid #ccc;padding:10px;margin-top:10px;"><strong>Special Instructions: </strong> ' . $user_instructions . '</div>';
	
	
	$rs_items->add($order_sku);
}

$dl_order_items = Page::getControlById('dl_order_items');
$dl_order_items->setData($rs_items);

?>