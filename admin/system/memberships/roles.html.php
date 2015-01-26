<?php

FrameworkManager::loadLogic('role');
FrameworkManager::loadLibrary('string.urlmanipulator');

if (!$membership_id = Page::get('membershipid')) Page::redirect('index.html');

$return_path = Page::get('returnpath', 'roles.html');
$return_path = CWI_STRING_UrlManipulator::appendUrl($return_path, 'membershipid', $membership_id);

if ($remove_role = Page::get('removerole')) {
	RoleLogic::removeUserFromRole($membership_id, $remove_role);
	#Page::redirect('roles.html?membershipid='.$membership_id); // Refresh page list
	Page::redirect($return_path);
} else if ($add_role = Page::get('addrole')) {
	RoleLogic::addUserToRole($membership_id, $add_role);
	#Page::redirect('roles.html?membershipid='.$membership_id); // Refresh page list
	Page::redirect($return_path);
}

$current_roles = array(); // Placeholder for 

// Get all roles
$rs_roles = RoleLogic::getVisibleRoles();

// Get all roles for user
$rs_roles_for_user = RoleLogic::getAllRolesForUser($membership_id);
while ($role_struct = $rs_roles_for_user->getNext()) array_push($current_roles, $role_struct->name);

$rs_current_roles = new ResultSet();
$rs_candidate_roles = new ResultSet();

while ($role_struct = $rs_roles->getNext()) {
	if (in_array($role_struct->name, $current_roles)) {
		$rs_current_roles->add($role_struct);
	} else {
		$rs_candidate_roles->add($role_struct);
	}
}

$dl_current = Page::getControlById('dl_current');
$dl_current->setData($rs_current_roles);

$dl_candidate = Page::getControlById('dl_candidate');
$dl_candidate->setData($rs_candidate_roles);

?>