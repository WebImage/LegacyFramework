<?php

FrameworkManager::loadDAO('menu');

class MenuLogic {
	const MENU_LAYOUT_FLAT = 'f';
	const MENU_LAYOUT_HIERARCHY = 'h';
	
	public static function getMenuById($menu_id) {
		$dao = new MenuDAO();
		return $dao->load($menu_id);
	}
	
	public static function getMenus() {
		$dao = new MenuDAO();
		return $dao->getMenus();
	}
	
	public static function save($menu_struct) {
		$dao = new MenuDAO();
		return $dao->save($menu_struct);
	}
	
	public static function delete($menu_id) {
		$dao = new MenuDAO();
		return $dao->delete($menu_id);
	}
	
	/**
	 * @param int $menu_id
	 * @return Dictionary A dictionary of menu items with parent IDs as the key
	 */
	private static function getMenuParentStructure($menu_id) {
	
		$menu_items = MenuItemLogic::getMenuItemsByMenuId($menu_id);
	
		$parents = new Dictionary();
	
		while ($menu_item = $menu_items->getNext()) {
			$parent_id = strlen($menu_item->parent_id) > 0 ? $menu_item->parent_id : 0;
			if (!$parent = $parents->get($parent_id)) {
				$parent = new Collection();
				$parents->set($parent_id, $parent);
			}
			$parent->add($menu_item);
		}
	
		return $parents;
	}
	
	/**
	 * @param ResultSet $result_set The ResultSet on which to build the current level of menu items
	 * @param Dictionary $parents A lookup for parent ids (0=root)
	 * @param int $layout How the structure should be built: self::MENU_LAYOUT_FLAT or self::MENU_LAYOUT_HIERARCHY
	 * @param int $parent_id The current parent id of items to be retrieved (default = 0 = root)
	 * @param int $depth The current depth of the items being built
	 */
	private static function buildMenuStructure($result_set, $parents, $layout=null, $parent_id=0, $depth=1) {
		if (null !== $layout && !in_array($layout, array(self::MENU_LAYOUT_FLAT, self::MENU_LAYOUT_HIERARCHY))) throw new RuntimeException('Invalid layout passed');
	
		if ($items = $parents->get($parent_id)) {
	
			while ($item = $items->getNext()) {
	
				$item->depth = $depth;
	
				$result_set->add($item);
	
				if ($layout == self::MENU_LAYOUT_FLAT) {
					self::buildMenuStructure($result_set, $parents, $layout, $item->id, $depth+1);
						
				} else { // null || self::MENU_LAYOUT_HIERARCHY
					$item->_children = new ResultSet();
					self::buildMenuStructure($item->_children, $parents, $layout, $item->id, $depth+1);
				}
	
			}
				
		}
	
	}
	
	public static function getMenuStructure($menu_id) {
	
		$root = new ResultSet();
		self::buildMenuStructure($root, self::getMenuParentStructure($menu_id), self::MENU_LAYOUT_HIERARCHY);
		return $root;
	
	}
	
	public static function getFlatMenuStructure($menu_id) {
	
		$root = new ResultSet();
		self::buildMenuStructure($root, self::getMenuParentStructure($menu_id), self::MENU_LAYOUT_FLAT);
		return $root;
	
	}
}

?>