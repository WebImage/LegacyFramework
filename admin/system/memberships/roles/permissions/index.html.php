<?php

FrameworkManager::loadLogic('permission');

// Make sure all required ("default") permissions are installed from config file
PermissionLogic::installMissingPermissions();

if ($delete_permission = Page::get('delete')) {
		PermissionLogic::delete($delete_permission);
	NotificationManager::addMessage('Permission ' . $delete_permission . ' was successfully deleted.');
}

$permissions = PermissionLogic::getPermissions();

$dg_permissions = Page::getControlById('dg_permissions');
$dg_permissions->setData($permissions);

?>