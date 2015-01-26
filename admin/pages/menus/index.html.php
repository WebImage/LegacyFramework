<?php

FrameworkManager::loadLogic('menu');

$rs_menus = MenuLogic::getMenus();

if ($dg_menus = Page::getControlById('dg_menus')) {
	$dg_menus->setData($rs_menus);
}

?>