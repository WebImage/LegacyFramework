<?php

FrameworkManager::loadStruct('menu');

class MenuDAO extends DataAccessObject {
	var $updateFields = array('created', 'created_by', 'name', 'updated', 'updated_by');
	var $modelName = 'MenuStruct';
	
	function __construct() {
		$this->tableName = DatabaseManager::getTable('menus');
	}
	
	public function getMenus() {
		$sql_select = "
			SELECT *
			FROM `" . $this->tableName . "`
			ORDER BY name";
		return $this->selectQuery($sql_select, $this->modelName);
	}
	
}

?>