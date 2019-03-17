<?php

FrameworkManager::loadStruct('rolepermission');
class RolePermissionDAO extends DataAccessObject {
	var $modelName = 'RolePermissionStruct';
	var $updateFields = array('allow_create', 'allow_read', 'allow_update', 'allow_delete', 'created', 'created_by', 'updated', 'updated_by');
	var $primaryKey = array('role_id', 'permission');
	function __construct() {
		$this->tableName = DatabaseManager::getTable('roles_permissions');
	}
	
	function loadByRolePermission($role_id, $permission) {
		$sql_select = "
			SELECT *
			FROM " . $this->tableName . "
			WHERE role_id = '" . $this->safeString($role_id) . "' AND permission = '" . $permission . "'";
		$query = $this->selectQuery($sql_select, $this->modelName);
		return $query->getAt(0);
	}
	
	function deleteRolePermissions($role_id) {
		$sql_command = "DELETE FROM " . $this->tableName . " WHERE role_id = '" . $this->safeString($role_id) . "'";
		return $this->commandQuery($sql_command);
	}

}
?>