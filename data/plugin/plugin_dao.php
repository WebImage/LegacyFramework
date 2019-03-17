<?php

/**
 * DataAccessObject for Plugin
 * 
 * @author Robert Jones II 
 * @copyright Copyright (c) 2007 Corporate Web Image, Inc.
 * @package DataAccessObject
 * @version 1.0 (01/25/2010), Athena_v1.0
 */

// Load required data structures
FrameworkManager::loadStruct('plugin');

class PluginDAO extends DataAccessObject {
	var $modelName = 'PluginStruct';
	var $updateFields = array('created','created_by','enable','friendly_name','path','updated','updated_by','version');
	var $primaryKey = 'name';
	function __construct() {
		$this->tableName = DatabaseManager::getTable('plugins');
	}
	
	function getPlugins() {
		$sql_select = "
			SELECT * 
			FROM `" . $this->tableName . "`
			WHERE enable = 1
			ORDER BY friendly_name";
		return $this->selectQuery($sql_select, $this->modelName);
	}
	
	function getPluginByName($plugin_name) {
		$sql_select = "
			SELECT * 
			FROM `" . $this->tableName . "`
			WHERE name = '" . $this->safeString($plugin_name) . "'";
		$query = $this->selectQuery($sql_select, $this->modelName);
		return $query->getAt(0);
	}
	
	function getAllPlugins() {
		$sql_select = "
			SELECT * 
			FROM `" . $this->tableName . "`
			ORDER BY friendly_name";
		return $this->selectQuery($sql_select, $this->modelName);
	}
}

?>