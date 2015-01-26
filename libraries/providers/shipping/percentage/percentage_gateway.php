<?php

FrameworkManager::loadLogic('shippingmethod');
class PercentageGateway extends IShippingGateway {
	function getRates($shipping_method_id, $shipment, &$message) {
		$list = new Collection();
		
		if (!$method = ShippingMethodLogic::getShippingMethodById($shipping_method_id)) {
			$message = 'The shipping method could not be loaded.';
			return null;
		}

		$list->add( new ShippingPackageOption( new ShippingMethodIdentifier($method->name), $method->friendly_name, 20) );
		
		return $list;
	}
}

?>