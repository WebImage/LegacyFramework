<?php
/**
 * DataAccessObject for AssetCategories
 * 
 * @author Robert Jones II <support@corporatewebimage.com>
 * @copyright Copyright (c) 2007 Corporate Web Image, Inc.
 * @package DataAccessObject
 * @version 1.0 (10/01/2007), Athena_v1.0
 */
class AssetCategoryDAO extends DataAccessObject {
	
	var $tableName;
	var $primaryKey = 'id';
	var $updateFields = array('created', 'created_by', 'extensions', 'folder', 'name', 'parent_id', 'type', 'updated', 'updated_by');
	
	function AssetCategoryDAO() {
		$this->tableName = DatabaseManager::getTable('asset_categories');
	}
	
	function getCategories() {
		$sql_select = "
			SELECT * 
			FROM `" . $this->tableName . "`
			ORDER BY name";
		
		return $this->selectQuery($sql_select, $this->modelName);
	}
	
	function getCategoryByName($name) {
		$sql_select = "
			SELECT * 
			FROM `" . $this->tableName . "`
			WHERE name = '" . $this->safeString($name) . "'";
		$query = $this->selectQuery($sql_select, $this->modelName);

		return $query->getAt(0);
	}
	
}

?>