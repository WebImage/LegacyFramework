<?php
/**
 * 02/10/2010	(Robert Jones) Finally implemented addUserToRole, removeUserFromRole
 */
class SqlRoleProvider extends RoleProvider {
	/*function _addPermissionSet($permission, $create, $read, $update, $delete) {
		$roles_provide = Roles::getInstance();
		$_this->m_availablePermissions[$permission] = new Permission($create, $read, $update, $delete);
	}*/

	function addUserToRole($role, $user_obj=null, $is_primary=false) {
		if (is_null($user_obj)) {
			if (!$user_obj = Membership::getUser()) return false;
		}
		
		FrameworkManager::loadLogic('role');
		RoleLogic::addUserToRole($user_obj->getId(), $role, $is_primary);
	}
	function createRole() {}
	function deleteRole() {}
	function findUsersInRole() {}
	function getAllRoles() {}
	
	function _getAllPermissionsForUser($user_obj=null) {
		
		if (is_null($user_obj)) {
			if ($user = Membership::getUser()) {
				// Get Main Role Provider
				$role_provider = Singleton::getInstance('RoleProvider');
				
				// Load Roles Data Access Object
				FrameworkManager::loadDAO('role');
				$role_dao = new RoleDAO();
				
				// Get all permissions for this usre
				$permissions = $role_dao->getAllPermissionsForUser($user->getId());

				// Add permissions to core provider
				while ($permission = $permissions->getNext()) {
					
					if (isset($role_provider->m_availablePermissions[$permission->permission])) { // Already added
						$temp_permission = $role_provider->m_availablePermissions[$permission->permission];
						
						if (!empty($permission->allow_create))	$temp_permission->setCreate(true);
						if (!empty($permission->allow_read))	$temp_permission->setRead(true);
						if (!empty($permission->allow_update))	$temp_permission->setUpdate(true);
						if (!empty($permission->allow_delete))	$temp_permission->setDelete(true);
						
						// Reassign permission
						$role_provider->m_availablePermissions[$permission->permission] = $temp_permission;
					} else { // Add new permission
						$role_provider->m_availablePermissions[$permission->permission] = new Permission($permission->allow_create, $permission->allow_read, $permission->allow_update, $permission->allow_delete);
					}
				}
			} else return array();
		} else {
			return array();
		}
	}
	function getUsersInRole() {}
	
	function getUsersInRoles(array $role_names, $current_page=null, $results_per_page=null) {
		FrameworkManager::loadLogic('role');
		$rs_memberships = RoleLogic::getUsersInRoles($role_names, $current_page, $results_per_page);
		
		$memberships = array();
		
		while ($membership_struct = $rs_memberships->getNext()) {
			$membership = Membership::getMembershipUserFromMembershipStruct($membership_struct);
			array_push($memberships, $membership);
		}
		
		return $memberships;
		
	}
	
	function _getAllRolesForUser($user_obj=null) {
		FrameworkManager::loadDAO('role');
		
		$role_provider = Singleton::getInstance('RoleProvider');
		
		if (is_null($user_obj)) {
			if ($user_obj = Membership::getUser()) {
				
				$role_dao = new RoleDAO();
				$roles = $role_dao->getAllRolesForUser($user_obj->getId());
				
				while ($role = $roles->getNext()) {
					if (!in_array($role->name, $role_provider->m_roles)) array_push($role_provider->m_roles, $role->name);
				}
				return $role_provider->m_roles;
			} else return array();
		} else {
			$return_roles = array();
			
			$role_dao = new RoleDAO();
			$roles = $role_dao->getAllRolesForUser($user_obj->getId());

			while ($role = $roles->getNext()) {
				array_push($return_roles, $role->name);
			}
			return $return_roles;
		}
		return array();
	}
	
	function getRolesForUser($membership_user=null) {
		return SqlRoleProvider::_getAllRolesForUser($membership_user);
	}

	
	function isUserInRole($role_name, $user_obj=null) {
		if (is_numeric($user_obj)) $user_obj = Membership::getUser($user_obj);
		$roles = SqlRoleProvider::_getAllRolesForUser($user_obj);
		if (in_array($role_name, $roles)) return true;
		else return false;
	}
	function removeUserFromRole($role_name, $membership_user=null) {
		if (is_null($membership_user)) {
			if (!$membership_user = Membership::getUser()) return false;
		}
		$membership_id = $membership_user->getId();
		if (empty($membership_id)) return false;
		
		FrameworkManager::loadLogic('role');
		if ($role = RoleLogic::getRoleByName($role_name)) {
			return RoleLogic::removeUserFromRole($membership_id, $role->id);
		} else return false;
	}
	
	function roleExists() {}
	
	function getStartPage($membership_user=null) {
		if (is_null($membership_user)) {
			// Get currently logged in user
			if (!$membership_user = Membership::getUser()) return false;
		}
		FrameworkManager::loadLogic('role');
		
		if ($role_struct = RoleLogic::getPrimaryRoleForUser($membership_user->getId())) {
			if (empty($role_struct->start_page)) return false;
			return $role_struct->start_page;
		} else return false;
	}
	
	function _hasPermission($permission) {
		SqlRoleProvider::_getAllPermissionsForUser();
		$role_provider = Singleton::getInstance('RoleProvider');
		if (isset($role_provider->m_availablePermissions[$permission])) return $role_provider->m_availablePermissions[$permission];
		else return false;
	}
	
	function canCreate($permission) {
		if ($permission = SqlRoleProvider::_hasPermission($permission)) {
			return $permission->canCreate();
		} else return false;
	}
	function canRead($permission) {
		if ($permission = SqlRoleProvider::_hasPermission($permission)) {
			return $permission->canRead();
		} else return false;
	}
	function canUpdate($permission) {
		if ($permission = SqlRoleProvider::_hasPermission($permission)) {
			return $permission->canUpdate();
		} else return false;
	}
	function canDelete($permission) {
		if ($permission = SqlRoleProvider::_hasPermission($permission)) {
			return $permission->canDelete();
		} else return false;
	}
}

?>