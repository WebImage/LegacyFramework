<?php

define('ACTION_RESTORE', 'restore-settings');
define('USER_SEARCH_KEYWORD', 'user.search.keyword');
define('USER_SEARCH_PAGE', 'user.search.page');

FrameworkManager::loadLogic('membership');
FrameworkManager::loadLogic('role');

$keyword = Page::get('q');
$page = Page::get('p', 1);
$action = Page::get('action', ACTION_RESTORE);

if ($action == ACTION_RESTORE) {
	$keyword = SessionManager::get(USER_SEARCH_KEYWORD);
	$page = SessionManager::get(USER_SEARCH_PAGE);
	Page::set('q', $keyword);
	Page::set('page', $page);
} else {
	SessionManager::set(USER_SEARCH_KEYWORD, $keyword);
	SessionManager::set(USER_SEARCH_PAGE, $page);
}

if ($message = Page::get('message')) NotificationManager::addMessage($message);

$memberships = MembershipLogic::searchMembershipsByKeyword($keyword, $page, 20);

$membership_ids = array();
while ($membership = $memberships->getNext()) {
	array_push($membership_ids, $membership->id);
}
$user_role_lookup = RoleLogic::getRoleLookupForUsers($membership_ids);

while ($membership = $memberships->getNext()) {
	
	$membership->roles = '';
	
	if ($rs_roles = $user_role_lookup->get($membership->id)) {
		
		$roles = array();
		$is_admin = false;
		
		while ($role = $rs_roles->getNext()) {
			// Check if user is an admin
			if ($role->name == 'AdmBase') $is_admin = true;
			// Otherwise add the role to the list of roles, but only if it is visible
			else if ($role->visible == 1) array_push($roles, $role->name);
		}
		
		if ($is_admin) array_unshift($roles, 'Admin');
		
		
		$membership->roles = '';
		foreach($roles as $role) {
			$role_format = '<span class="tag">%s <a href="#" onclick="return false;"><i class="glyphicon glyphicon-remove"></i></a></span>';
			$role_format = '<span class="tag">%s</span>';
			$membership->roles .= sprintf($role_format, $role);
		}
	}
	
}

$dg_membership = Page::getControlById('dg_membership');
$dg_membership->setData($memberships);

?>