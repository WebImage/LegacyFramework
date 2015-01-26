<?php
/**
 * DataAccessObject for Roles
 * 
 * @author Robert Jones II <support@corporatewebimage.com>
 * @copyright Copyright (c) 2007 Corporate Web Image, Inc.
 * @package DataAccessObject
 * @version 1.0 (10/01/2007), Athena_v1.0
 */

// Load required data structures
FrameworkManager::loadStruct('role');
FrameworkManager::loadStruct('membershiprolepermission');

class RoleDAO extends DataAccessObject {
	var $modelName = 'RoleStruct';
	var $primaryKey = 'id';
	var $updateFields = array('created', 'created_by', 'description', 'name', 'start_page', 'updated', 'updated_by', 'visible');
		
	function RoleDAO() {
		$this->tableName = DatabaseManager::getTable('roles');
	}
	
	function getRoles() {
		$sql_select = "
			SELECT *
			FROM `" . $this->tableName . "`
			ORDER BY name";
		return $this->selectQuery($sql_select, $this->modelName);
	}
	
	function getRolesByNames(array $names) {
		$sql_select = "
			SELECT *
			FROM `" . $this->tableName . "`
			WHERE name IN ('" . implode("','", $names) . "')
			ORDER BY name";
		return $this->selectQuery($sql_select, $this->modelName);
	}
	
	function getVisibleRoles() {
		$sql_select = "
			SELECT *
			FROM `" . $this->tableName . "`
			WHERE visible = 1
			ORDER BY name";
		return $this->selectQuery($sql_select, $this->modelName);
	}
	
	function getPermissionsByRoleId($role_id) {
		$model_name = 'MembershipRolePermissionStruct';
		
		$sql_select = "
			SELECT p.permission, rp.allow_create, rp.allow_read, rp.allow_update, rp.allow_delete
			FROM " . DatabaseManager::getTable('permissions') . " p
				LEFT JOIN " . DatabaseManager::getTable('roles_permissions') . " rp ON rp.permission = p.permission AND rp.role_id = '" . $this->safeString($role_id) . "'
			ORDER BY p.permission";
		return $this->selectQuery($sql_select, $model_name);
	}
	
	function getAllPermissionsForUser($user_id) {
		$model_name = 'MembershipRolePermissionStruct';
		$select_sql = "
			SELECT p.permission, rp.allow_create, rp.allow_read, rp.allow_update, rp.allow_delete
			FROM " . DatabaseManager::getTable('memberships_roles') . " mr
				LEFT JOIN " . DatabaseManager::getTable('roles_permissions') . " rp ON rp.role_id = mr.role_id
				LEFT JOIN " . DatabaseManager::getTable('permissions') . " p ON p.permission = rp.permission
			WHERE mr.membership_id = '" . $user_id . "'";
		return $this->selectQuery($select_sql, $model_name);
	}
	
	function getAllRolesForUser($user_id) {
		$select_sql = "
			SELECT r.*, mr.is_primary
			FROM " . DatabaseManager::getTable('memberships_roles') . " mr
			INNER JOIN " . DatabaseManager::getTable('roles') . " r ON r.id = mr.role_id
			WHERE mr.membership_id = '" . $user_id . "'";
		return $this->selectQuery($select_sql, $this->modelName);
	}
	function getVisibleRolesForUser($user_id) {
		$select_sql = "
			SELECT r.*, mr.is_primary
			FROM `" . DatabaseManager::getTable('memberships_roles') . "` mr
			INNER JOIN `" . DatabaseManager::getTable('roles') . "` r ON r.id = mr.role_id
			WHERE 
				mr.membership_id = '" . $user_id . "' AND
				r.visible = 1";
		return $this->selectQuery($select_sql, $this->modelName);
	}
	
	function getRolesForUsers($user_ids) {
		$select_sql = "
			SELECT r.*, mr.is_primary, mr.membership_id
			FROM `" . DatabaseManager::getTable('memberships_roles') . "` mr
			INNER JOIN `" . DatabaseManager::getTable('roles') . "` r ON r.id = mr.role_id
			WHERE 
				mr.membership_id IN ('" . implode("','", $user_ids) . "')";
		return $this->selectQuery($select_sql, $this->modelName);
	}
	
	function getRoleByName($role_name) {
		$select_sql = "
			SELECT description, id, name, start_page
			FROM `" . $this->tableName . "`
			WHERE name = '" . $role_name . "'";
		$query = $this->selectQuery($select_sql, $this->modelName);
		return $query->getAt(0);
			
	}
	
	function addUserToRole($user_id, $role_id, $is_primary_role=false) {
		$sql_select = "SELECT COUNT(*) AS num_records FROM `" . DatabaseManager::getTable('memberships_roles') . "` WHERE membership_id = '" . $this->safeString($user_id) . "' AND role_id = '" . $this->safeString($role_id) . "'";
		
		$results = $this->selectQuery($sql_select);
		
		if ($result = $results->getAt(0)) {
			if ($result->num_records > 0) {
				return false;
			}
		}
		$is_primary_role = ($is_primary_role===true || $is_primary_role == 1) ? 1:0;
		
		if ($is_primary_role == 1) {
			$this->commandQuery("UPDATE `" . DatabaseManager::getTable('memberships_roles') . " SET is_primary = 0 WHERE membership_id = '" . $this->safeString($user_id) . "'");
		}
		
		return $this->commandQuery("INSERT INTO `" . DatabaseManager::getTable('memberships_roles') . "` (membership_id, role_id, is_primary) VALUES('" . $this->safeString($user_id) . "', '" . $this->safeString($role_id) . "', '" . $this->safeString($is_primary_role) . "')");
	}
	
	function removeUserFromRole($user_id, $role_id) {
		$sql_command = "DELETE FROM `" . DatabaseManager::getTable('memberships_roles') . "` WHERE membership_id = '" . $this->safeString($user_id) . "' AND role_id = '" . $this->safeString($role_id) . "'";
		return $this->commandQuery($sql_command);
		
	}
	
	function getPrimaryRoleForUser($user_id) {
		$select_sql = "
			SELECT r.*, mr.is_primary
			FROM " . DatabaseManager::getTable('memberships_roles') . " mr
			INNER JOIN " . DatabaseManager::getTable('roles') . " r ON r.id = mr.role_id
			WHERE mr.membership_id = '" . $user_id . "' AND mr.is_primary = 1";
		$query = $this->selectQuery($select_sql, $this->modelName);
		return $query->getAt(0);
	}
	
}

?>