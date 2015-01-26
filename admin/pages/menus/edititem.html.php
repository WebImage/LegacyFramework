<?php

FrameworkManager::loadLogic('menuitem');

$menu_item = Page::getStruct('menu_item');

if (Page::isPostBack()) {

	if (empty($menu_item->name)) ErrorManager::addError('Name is required');
	if (empty($menu_item->link)) ErrorManager::addError('Link is required');
	if (strlen($menu_item->parent_id) == 0) ErrorManager::addError('Parent is required');
	
	if (!ErrorManager::anyDisplayErrors()) {
		
		$menu_item = MenuItemLogic::save($menu_item);
		Page::redirect('items.html?menuid=' . $menu_item->menu_id);
		
	}
	
} else {

	if ($menu_item_id = Page::get('id')) {

		$menu_item = MenuItemLogic::getMenuItemById($menu_item_id);
		
	} else {
		
		$menu_id = Page::get('menuid');
		
		if (empty($menu_id)) Page::redirect('index.html?error=MISSING+MENU');
		$menu_item->menu_id = $menu_id;
		
	}
}

$rs_menu_items = MenuItemLogic::getMenuItemsByMenuId($menu_item->menu_id);
$rs_parents = new ResultSet();

while ($item = $rs_menu_items->getNext()) {
	// Make sure item cannot point to itself
	if ($item->id != $menu_item->id) $rs_parents->add($item);
}

if ($menu_item_parent_id = Page::getControlById('menu_item_parent_id')) {

	$menu_item_parent_id->setData($rs_parents);

}

Page::setStruct('menu_item', $menu_item);

?>