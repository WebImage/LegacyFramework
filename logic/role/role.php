<?php

FrameworkManager::loadDAO('role');

class RoleLogic{

	public static function getRoleById($role_id) {
		$role_dao = new RoleDAO();
		return $role_dao->load($role_id);
	}
	
	public static function getRoles() {
		$role_dao = new RoleDAO();
		return $role_dao->getRoles();
	}
	
	public static function getRolesByNames(array $names) {
		$role_dao = new RoleDAO();
		return $role_dao->getRolesByNames($names);
	}
	
	public static function getRoleIdsByNames(array $names) {
		$roles = RoleLogic::getRolesByNames($names);
		$ids = array();
		
		while ($role = $roles->getNext()) {
			array_push($ids, $role->id);
		}
		
		return $ids;
	}
	
	public static function getVisibleRoles() {
		$role_dao = new RoleDAO();
		return $role_dao->getVisibleRoles();
	}
	
	public static function getUsersInRoles($role_names, $current_page=null, $results_per_page=null) {
		
		$role_ids = RoleLogic::getRoleIdsByNames($role_names);
		
		FrameworkManager::loadLibrary('db.daosearch');
		
		$search = new DAOSearch('memberships_roles', $current_page, $results_per_page);
		$search->addSearchField( new DAOSearchFieldValues('memberships_roles', 'role_id', $role_ids) );
		$join = new DAOJoin('memberships', DAOJoin::JOIN_INNER, array('memberships.id'=>'memberships_roles.membership_id'), DAOSearch::ALL_FIELDS);
		$search->addJoin($join);
		
		FrameworkManager::loadDAO('membership');
		
		$dao = new MembershipDAO();
		return $dao->search($search);
		
	}
	
	public static function getPermissionsByRoleId($role_id) {
		$role_dao = new RoleDAO();
		return $role_dao->getPermissionsByRoleId($role_id);
	}
	
	public static function getAllPermissionsForUser($user_id) {
		$role_dao = new RoleDAO();
		return $role_dao->getAllPermissionsForUser($user_id);
	}
	
	public static function getAllRolesForUser($user_id) {
		$role_dao = new RoleDAO();
		return $role_dao->getAllRolesForUser($user_id);
	}
	
	public static function getVisibleRolesForUser($user_id) {
		$role_dao = new RoleDAO();
		return $role_dao->getVisibleRolesForUser($user_id);
	}
	
	/**
	 * @return Dictionary a lookup of roles by user
	 **/
	public static function getRoleLookupForUsers($user_ids) {
		$role_dao = new RoleDAO();
		$rs_roles = $role_dao->getRolesForUsers($user_ids);
		
		$d = new Dictionary(); // The lookup
		
		while ($role = $rs_roles->getNext()) {
			
			if (!$member_roles = $d->get($role->membership_id)) {
				$member_roles = new Collection();
				$d->set($role->membership_id, $member_roles);
			}

			$member_roles->add($role);
			
		}
		
		return $d;
	}
	
	public static function getRoleByName($role_name) {
		$role_dao = new RoleDAO();
		return $role_dao->getRoleByName($role_name);
	}
	
	public static function getRoleIdByName($role_name) {
		if ($role_struct = RoleLogic::getRoleByName($role_name)) {
			return $role_struct->id;
		} else return false;
	}
	
	public static function save($role_struct) {
		$role_dao = new RoleDAO();
		return $role_dao->save($role_struct);
	}
	
	public static function addUserToRole($user_id, $role_id, $is_primary=false) {
		if (empty($user_id) || empty($role_id)) return false;
		if (!is_numeric($role_id) && is_string($role_id)) {
			if (!$role_struct = RoleLogic::getRoleByName($role_id)) return false;
			$role_id = $role_struct->id;
		}
		$role_dao = new RoleDAO();
		return $role_dao->addUserToRole($user_id, $role_id, $is_primary);
	}
	
	public static function removeUserFromRole($user_id, $role_id) {
		if (empty($user_id) || empty($role_id)) return false;
		$role_dao = new RoleDAO();
		return $role_dao->removeUserFromRole($user_id, $role_id);
	}
	
	public static function getPrimaryRoleForUser($user_id) {
		$role_dao = new RoleDAO();
		$role = $role_dao->getPrimaryRoleForUser($user_id);
		return $role;
	}
	
	// Role Permissions
	public static function deleteRolePermissions($role_id) {
		FrameworkManager::loadDAO('rolepermission');
		$role_permission_dao = new RolePermissionDAO();
		$role_permission_dao->deleteRolePermissions($role_id);
	}
	
	public static function saveRolePermission($role_permission) {
		FrameworkManager::loadDAO('rolepermission');
		$role_permission_dao = new RolePermissionDAO();

		if (!$role_permission_dao->loadByRolePermission($role_permission->role_id, $role_permission->permission)) { // New record
			$role_permission_dao->setForceInsert(true);
		}
		
		$role_permission_dao->save($role_permission);
	}
	
	public static function addRolePermission($role_permission) {
		FrameworkManager::loadDAO('rolepermission');
		$role_permission_dao = new RolePermissionDAO();
		
		$role_permission_dao->setForceInsert(true);
		$role_permission_dao->save($role_permission);
	}
	
}

?>