<?php

FrameworkManager::loadLogic('menu');

$menu = Page::getStruct('menu');

if (Page::isPostBack()) {

	if (empty($menu->name)) ErrorManager::addError('Name is required');
	
	if (!ErrorManager::anyDisplayErrors()) {
		
		$menu = MenuLogic::save($menu);
		Page::redirect('items.html?menuid=' . $menu->id);
		
	}
	
} else {

	if ($menu_id = Page::get('id')) {
	
		$menu = MenuLogic::getMenuById($menu_id);
		
	}

}

Page::setStruct('menu', $menu);

?>