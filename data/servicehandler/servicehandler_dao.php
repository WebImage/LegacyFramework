<?php

FrameworkManager::loadStruct('servicehandler');
class ServiceHandlerDAO extends DataAccessObject {
	var $modelName = 'ServiceHandlerStruct';
	var $updateFields = array('class_file', 'config', 'plugin', 'sortorder');
	var $primaryKey = array('type', 'class_name');
	function __construct() {
		$this->tableName = DatabaseManager::getTable('service_handlers');
	}
	
	function getServiceHandlersByType($type) {
		$sql_select = "
			SELECT *
			FROM `" . $this->tableName . "` service_handlers
			WHERE 
				`type` = '" . $this->safeString($type) . "'";
		return $this->selectQuery($sql_select, $this->modelName);		
	}

	function getServiceHandler($type, $class_name) {
		$sql_select = "
			SELECT *
			FROM `" . $this->tableName . "` service_handlers
			WHERE 
				`type` = '" . $this->safeString($type) . "' AND
				class_name = '" . $this->safeString($class_name) . "'";
		return $this->selectQuery($sql_select, $this->modelName)->getAt(0);		
	}
	
}

?>