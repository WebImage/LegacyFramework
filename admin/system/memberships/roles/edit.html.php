<?php

FrameworkManager::loadLogic('role');

$role = Page::getStruct('role');

if (Page::isPostBack()) {

	$role = RoleLogic::save($role);
	Page::redirect('index.html');

} else {

	if ($role_id = Page::get('roleid')) {
		$role = RoleLogic::getRoleById($role_id);
	}

}

Page::setStruct('role', $role);

?>