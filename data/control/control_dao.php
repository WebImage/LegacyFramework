<?php
/**
 * DataAccessObject for Controls
 * 
 * @author Robert Jones II <support@corporatewebimage.com>
 * @copyright Copyright (c) 2007 Corporate Web Image, Inc.
 * @package DataAccessObject
 * @version 1.0 (10/01/2007), Athena_v1.0
 */

// Load required data structures
FrameworkManager::loadStruct('control');

class ControlDAO extends DataAccessObject {
	var $modelName = 'ControlStruct';
	var $primaryKey = 'id';
	var $updateFields = array('class_name', 'created', 'created_by', 'enable', 'file_src', 'label', 'updated', 'updated_by');
	
	function __construct() {
		$this->tableName = DatabaseManager::getTable('controls');
	}
	
	function getControls() {
		$select_sql = "
			SELECT *
			FROM " . $this->tableName . "
			WHERE enable = 1
			ORDER BY label";

		return $this->selectQuery($select_sql, $this->modelName);
	}
	
	function getControlByClassName($class_name) {
		$select_sql = "
			SELECT *
			FROM " . $this->tableName . "
			WHERE class_name = '" . $class_name . "'";
		
		return $this->selectQuery($select_sql, $this->modelName)->getAt(0);
	}
	
	function getAllControls() {
		$select_sql = "
			SELECT *
			FROM " . $this->tableName . "
			ORDER BY enable DESC, label";

		return $this->selectQuery($select_sql, $this->modelName);
	}
	
}

?>