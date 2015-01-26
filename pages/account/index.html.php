<?php
FrameworkManager::loadLogic('order');

if (!$user = Membership::getUser()) Page::redirect(ConfigurationManager::get('DIR_WS_HOME') . 'login.html');

$orders = OrderLogic::getOrdersByCustomerId($user->getId());

$dg_order = Page::getControlById('dg_order');
$rs_orders = new ResultSet();
$count = 0;
while ($order = $orders->getNext()) {
	$count ++;
	$ship_to = array();
	$order_shipments = OrderLogic::getOrderShipmentsByOrderId($order->id);

	while ($shipment = $order_shipments->getNext()) {
		array_push($ship_to, $shipment->customer_address_name);
	}

	$o = new stdClass();
	$o->id = $order->id;
	$o->display_id = $o->id;
	if (strlen($o->display_id) < 3) $o->display_id = sprintf('%03d', $o->display_id);
	
	$o->date = '';
	$o->ship_to = implode(', ', $ship_to);
	
	if (!empty($order->created)) {
		$o->date = database_format_date('M d, Y', $order->created);
		$o->display_id = OrderHelper::getFormattedIdFromStruct($order);
	}

	$o->order_status = $order->order_status;
	$o->total = '$' . sprintf('%.2f', $order->total);
	
	$rs_orders->add($o);
}
$dg_order->setData($rs_orders);

?>