<?php

FrameworkManager::loadLogic('permission');
FrameworkManager::loadStruct('permission');

$permission = Page::get('permission');

if (Page::isPostBack()) {
	
	if (empty($permission)) {
		ErrorManager::addError('Please enter a name for this permission.');
	} else if (PermissionLogic::getPermission($permission)) {
		ErrorManager::addError('The permission entered is already in use.');
	}

	if (!ErrorManager::anyDisplayErrors()) {
		$permission_struct = new PermissionStruct();
		$permission_struct->permission = $permission;
		
		PermissionLogic::save($permission_struct);
		if (!Page::get('add_another')) {
			Page::redirect('index.html');
		}
	}
}

?>