<?php

/**
 * DataAccessObject for Permissions
 * 
 * @author Robert Jones II 
 * @copyright Copyright (c) 2007 Corporate Web Image, Inc.
 * @package DataAccessObject
 * @version 1.0 (05/23/2009), Athena_v1.0
 */

// Load required data structures
FrameworkManager::loadStruct('permission');

class PermissionDAO extends DataAccessObject {
	var $modelName = 'PermissionStruct';
	var $updateFields = array('created','created_by','description','updated','updated_by');
	var $primaryKey = 'permission';
	
	function PermissionDAO() {
		$this->tableName = DatabaseManager::getTable('permissions');
		$this->setForceInsert(true);
	}
	
	function getPermissions() {
		$sql_select = "
			SELECT *
			FROM " . $this->tableName . "
			ORDER BY permission";
		return $this->selectQuery($sql_select);
	}
}

?>