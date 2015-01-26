<?php

FrameworkManager::loadLogic('membership');
FrameworkManager::loadLogic('role');
FrameworkManager::loadLogic('parameter');
FrameworkManager::loadLogic('membership_parameter');

$membership_id = Page::get('membershipid');

if (!$user = Membership::getUser($membership_id)) Page::redirect('index.html');

$rs_roles = RoleLogic::getVisibleRolesForUser($membership_id);

if ($username = Page::getControlById('username')) $username->setText($user->getUsername());
if ($email = Page::getControlById('email')) $email->setText($user->getEmail());


if ($dg_roles = Page::getControlById('dg_roles')) {
	
	$dg_roles->setData($rs_roles);
	
}

$rs_visible_roles = RoleLogic::getVisibleRoles();
$rs_candidate_roles = new ResultSet();

$current_roles = array();
while ($role_struct = $rs_roles->getNext()) array_push($current_roles, $role_struct->name);

while ($role_struct = $rs_visible_roles->getNext()) {
	if (!in_array($role_struct->name, $current_roles)) {
		$rs_candidate_roles->add($role_struct);
	}
}

if ($rs_candidate_roles->getCount() == 0) {
	
	if ($candidate_roles = Page::getControlById('candidate_roles')) {
		
		$candidate_roles->visible(false);
		
	}
	
} else {
	
	if ($add_role = Page::getControlById('addrole')) {
		
		$add_role->setData($rs_candidate_roles);
		
	}
	
}

?>