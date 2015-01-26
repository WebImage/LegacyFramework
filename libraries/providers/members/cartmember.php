<?php

/* Need a way to cut down on these loads, they add a huge chunk to memory usage (~1.5mb on last check) */
FrameworkManager::loadLibrary('store'); 
FrameworkManager::loadLogic('cart');
FrameworkManager::loadStruct('cart');
include('sqlmembershipprovider.php');

class SqlCartMembershipProvider extends SqlMembershipProvider {	
	var $cart;
	
	function _sessionToCustomer($membership_user) {
		$session_key = SessionManager::getId();
		CartLogic::sessionToCustomer($session_key, $membership_user->getId());
	}
	
	/*function createUser($user_obj) {
		if ($membership_user = SqlMembershipProvider::createUser($user_obj)) {
			SqlCartMembershipProvider_transferSessionCart($membership_user);
			return $membership_user;
		} else return false;
	}*/
	
	function createUserAndLogin($user_obj) {
		if ($membership_user = SqlMembershipProvider::createUserAndLogin($user_obj)) {
			SqlCartMembershipProvider::_sessionToCustomer($membership_user);
			return $membership_user;
		} else return false;
	}
	
	function validateUser($username, $password) {
		$session_key = SessionManager::getId();
		if ($membership_user = SqlMembershipProvider::validateUser($username, $password)) {
			CartLogic::sessionToCustomer($session_key, $membership_user->getId());
			return $membership_user;
		} else return false;
	}

	function getCart() {
		$_this = Singleton::getInstance('SqlCartMembershipProvider');
		if (is_null($_this->cart)) {
			$_this->cart = SqlCartMembershipProvider::refreshCart();
			return $_this->cart;
		} else {
			return $_this->cart;
		}
	}
	
	function refreshCart() {
		
		$cart_items = SqlCartMembershipProvider::getCartItems();
		$shopping_cart = new ShoppingCart();
		
		if ($user = SqlCartMembershipProvider::getUser()) {
			$shopping_cart->setMembershipId($user->getId());
		}
		
		while ($cart_item = $cart_items->getNext()) {
			$parameters = SqlCartMembershipProvider::_getCartItemParameters($cart_item->id);
			$shopping_cart->addCartItemByStruct($cart_item, $parameters);
		}
		
		// Validate cart items
		
		$shopping_cart_items = $shopping_cart->getCartItems();
		
		foreach($shopping_cart_items as $shopping_cart_item) {
			
			if (!$shopping_cart_item->isValid()) {
				
				CartLogic::deleteCartItemById($shopping_cart_item->getId());
				
				$shopping_cart->removeCartItemById($shopping_cart_item->getId());
				
			}
			
		}
		
		$_this->_cart = $shopping_cart;
		return $_this->_cart;
	}
	
	function getCartItems() {
		if ($user = SqlCartMembershipProvider::getUser()) {
			return CartLogic::getCartByCustomerId($user->getId());
		} else {
			return CartLogic::getCartBySessionKey(SessionManager::getId());
		}
	}
	
	function _getCartItemParameters($cart_id) {
		$return = array();
		$parameters = CartLogic::getCartParametersByCartId($cart_id);
		while ($parameter = $parameters->getNext()) {
			array_push($return, $parameter);
		}
		return $return;
	}
	function _getCartItemAddresses($cart_id) {
		$return = array();
		$addresses = CartLogic::getCartAddressesByCartId($cart_id);
		while ($address = $addresses->getNext()) {
			$cart_address = new ShoppingCartItemAddress($address);
			array_push($return, $cart_address);
		}
		return $return;
	}
	
	function getCartTotalItems() {
		if ($user = SqlCartMembershipProvider::getUser()) {
			return CartLogic::getCartTotalItemsByCustomerId($user->getId());
		} else {
			return CartLogic::getCartTotalItemsBySessionKey(SessionManager::getId());
		}
	}
	function getCartItemBySkuId($sku_id) {
		if ($user = SqlCartMembershipProvider::getUser()) {
			return CartLogic::getCartItemByCustomerIdAndSkuId($user->getId(), $sku_id);
		} else {
			return CartLogic::getCartItemBySessionKeyAndSkuId(SessionManager::getId(), $sku_id);
		}
	}
	
	function addCartItem($sku_struct, $quantity=1, &$message=null, $merge_item=true) {
		/**
		 * $merge_item determines whether we should merge $sku_struct with cart items that might already
		 */
		if ($merge_item) {
			$cart_item = $this->getCartItemBySkuId($sku_struct->id);
		}

		if (empty($cart_item)) {
			$cart_item = new CartStruct();
		}
		
		if (!empty($quantity) && is_numeric($quantity) && $quantity > 0) {
			$cart_item->quantity = $quantity;
			$cart_item->sku_id = $sku_struct->id;
			
			if ($user = SqlCartMembershipProvider::getUser()) {
				$cart_item->customer_id = $user->getId();
			} else {
				$cart_item->session_key = SessionManager::getId();
			}
			#$test = ShoppingCartItemFactory::createShoppingCartItemFromCartStruct($cart_item);
			self::refreshCart();
			return CartLogic::save($cart_item);
		} else {
			if (!empty($cart_item->id)) CartLogic::deleteCartItemById($cart_item->id);
			return false;
		}
	}
	
	function updateCartItemById($cart_id, $quantity=1) {
		$is_valid = true;
		
		if ($cart_item = CartLogic::getCartItemById($cart_id)) {
			$cart_item->quantity = $quantity;
			
			if ($user = SqlCartMembershipProvider::getUser()) {
				if ($cart_item->customer_id != $user->getId()) $is_valid = false;
			} else {
				if ($cart_item->session_key != SessionManager::getId()) $is_valid = false;
			}
			if ($is_valid) {
				FrameworkManager::loadLogic('customercartshipment');
				$addresses = CustomerCartShipmentLogic::getCartAddressesByCartId($cart_id);
				
				if (is_numeric($quantity) && $quantity > 0) {
					if ($addresses->getCount() == 1) {
						$address = $addresses->getAt(0);
						$address->quantity = $quantity;
						CustomerCartShipmentLogic::saveCartAddress($address);
					}
					return CartLogic::save($cart_item);
				} else {
					if ($addresses->getCount() == 1) {
						$address = $addresses->getAt(0);
						$address->quantity = $quantity;
						CustomerCartShipmentLogic::deleteCartAddressByCartId($cart_item->id);
					}
					return CartLogic::deleteCartItemById($cart_item->id);
				}
			}
		}
		return false;
	}
		
	function addCartItemAddress($sku_id, $address_id, $quantity) {
		$cart_item = $this->getCartItemBySkuId($sku_id);
		
		if ($user = SqlCartMembershipProvider::getUser()) {

			CartLogic::setCartItemAddressId($user->getId(), $cart_item->id, $address_id, $quantity);
			
		}
	}
		
	/*
	function quantityInCart($item_id=null) {
		if ($user = SqlCartMembershipProvider::getUser()) {
			return CartLogic::getQuantityByCustomerIdAndSkuId($user->getId, $item_id);
		} else {
			return CartLogic::getItemQuantityBySessionKeyAndSkuId(SessionManager::getId(), $item_id);
		}
	}
	*/
	
	function removeCartItem($sku_struct) {
	
	}
	
	function updateCartItem($sku_struct, $quantity=1) {
	
	}
	
	function emptyCart() {
		if ($user = SqlCartMembershipProvider::getUser()) {
			CartLogic::deleteCartByCustomerId($user->getId());
		} else {
			CartLogic::deleteCartBySessionKey(SessionManager::getId());
		}
		// Reset cart object to force system to refresh cart if it is needed after emptyCart() is called.
		$_this->cart = null;
		return true;
	}
}

?>