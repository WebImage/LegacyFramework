<?php

FrameworkManager::loadStruct('menuitem');

class MenuItemDAO extends DataAccessObject {
	var $updateFields = array('created', 'created_by', 'menu_id', 'name', 'parent_id', 'sortorder', 'updated', 'updated_by', 'url');
	var $modelName = 'MenuItemStruct';
	
	function __construct() {
		$this->tableName = DatabaseManager::getTable('menu_items');
	}
	
	public function getMenuItemsByMenuId($menu_id) {
		$sql_select = "
			SELECT * 
			FROM `" . $this->tableName . "`
			WHERE menu_id = '" . $this->safeString($menu_id) . "'
			ORDER BY sortorder";
		return $this->selectQuery($sql_select, $this->modelName);
	}
}

?>