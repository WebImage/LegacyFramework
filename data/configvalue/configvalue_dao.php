<?php

FrameworkManager::loadStruct('configvalue');

class ConfigValueDAO extends DataAccessObject {
	var $modelName = 'ConfigValueStruct';
	var $primaryKey = array('group_key', 'field');
	var $updateFields = array('locked', 'plugin_id', 'value');
	
	function __construct() {
		$this->tableName = DatabaseManager::getTable('config_values');
	}
	
	public function getConfigValues() {
		$sql_select = "
			SELECT *
			FROM `" . $this->tableName . "`
			ORDER BY group_key, field";
		return $this->selectQuery($sql_select, $this->modelName);
	}
	
	public function getConfigValue($group_key, $field) {
		
		$sql_select = "
			SELECT *
			FROM `" . $this->tableName . "`
			WHERE 
				group_key = '" . $this->safeString($group_key) . "' AND
				field = '" . $this->safeString($field) . "'";
		
		return $this->selectQuery($sql_select, $this->modelName)->getAt(0);
		
	}
	
}

?>