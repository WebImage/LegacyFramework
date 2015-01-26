<?php
// TODO: refactor code to make RoleProvider an interface
class RoleProvider extends ProviderBase {
	var $m_availablePermissions = array();
	var $m_roles = array();
	
	//function applicationName() {}
	function addUserToRole($role, $user_obj=null, $is_primary=false) {}
	function createRole() {}
	function deleteRole() {}
	function findUsersInRole() {}
	function getAllRoles() {}
	function getRolesForUser($membership_user=null) {}
	function getUsersInRole() {}
	function isUserInRole($role_name, $user_id=null) {} // $user_id is optional
	function removeUserFromRole($role_name, $membership_user=null) {}
	function roleExists() {}
	function getStartPage() {}
	// New
	function hasPermission($permission) { return false; }
	function canCreate($permission) { return false; }
	function canRead($permission) { return false; }
	function canUpdate($permission) { return false; }
	function canDelete($permission) { return false; }
}
