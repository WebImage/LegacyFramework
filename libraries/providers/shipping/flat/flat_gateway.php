<?php

FrameworkManager::loadLogic('shippingmethod');
class FlatGateway extends IShippingGateway {
	function getRates($shipping_method_id, $shipment, &$message) {
		$list = new Collection();
		
		if (!$method = ShippingMethodLogic::getShippingMethodById($shipping_method_id)) {
			$message = 'The shipping method could not be loaded.';
			return null;
		}
		
		$method_id = new ShippingMethodIdentifier($method->name);
		$method_friendly_name = $method->friendly_name;
		$method_base_price = $method->base_price;
		
		$shipping_package_option = new ShippingPackageOption( $method_id, $method_friendly_name, $method_base_price );

		$list->add( $shipping_package_option );
		
		return $list;
	}
}

?>