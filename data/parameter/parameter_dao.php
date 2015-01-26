<?php

/**
 * DataAccessObject for parameter names
 **/
FrameworkManager::loadStruct('parameter');
class ParameterDAO extends DataAccessObject {
	
	var $modelName = 'ParameterStruct';
	var $updateFields = array('config', 'created', 'created_by', 'description', 'group', 'input_element', 'name', 'sortorder', 'updated', 'updated_by');
	var $primaryKey = array('type', 'key');
	
	function __construct() {
		$this->tableName = DatabaseManager::getTable('parameters');
	}
	
	/**
	 * Returns a list of parameters by type
	 * 
	 * @parameter string $type a magic value that allows different modules to use this table
	 * @return ResultSet
	 **/
	public function getParametersByType($type) {
		$sql_select = "
			SELECT *
			FROM `" . $this->tableName . "` parameters
			WHERE `type` = '" . $this->safeString($type) . "'
			ORDER BY `group`, sortorder, name";
			
		return $this->selectQuery($sql_select, $this->modelName);
	}
	
	public function getParameterByTypeAndKey($type, $key) {
		$sql_select = "
			SELECT *
			FROM `" . $this->tableName . "` parameters
			WHERE 
				`type` = '" . $this->safeString($type) . "' AND
				`key` = '" . $this->safeString($key) . "'
			ORDER BY `group`, sortorder, name";
			
		return $this->selectQuery($sql_select, $this->modelName)->getAt(0);
	}
	
	public function deleteParameter($type, $key) {
		$sql_command = "
			DELETE 
			FROM `" . $this->tableName . "` 
			WHERE 
				`type` = '" . $this->safeString($type) . "' AND 
				`key` = '" . $this->safeString($key) . "'";
		return $this->commandQuery($sql_command);
	}
}

?>