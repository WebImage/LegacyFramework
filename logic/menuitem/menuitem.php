<?php

FrameworkManager::loadDAO('menuitem');

class MenuItemLogic {
	
	public static function getMenuItemById($menu_item_id) {
		$dao = new MenuItemDAO();
		return $dao->load($menu_item_id);
	}
	
	public static function getMenuItemsByMenuId($menu_id) {
		$dao = new MenuItemDAO();
		return $dao->getMenuItemsByMenuId($menu_id);
	}
	
	public static function save($menu_item_struct) {
		$dao = new MenuItemDAO();
		return $dao->save($menu_item_struct);
	}
	
	public static function delete($menu_item_id) {
		$dao = new MenuItemDAO();
		return $dao->delete($menu_item_id);
	}
				
}

?>