<?php
/**
 * DataAccessObject for Category
 * 
 * @author Robert Jones II <support@corporatewebimage.com>
 * @copyright Copyright (c) 2007 Corporate Web Image, Inc.
 * @package DataAccessObject
 * @version 1.0 (10/01/2007), Athena_v1.0
 */

// Load required data structures
FrameworkManager::loadStruct('category');

class CategoryDAO extends DataAccessObject {
	var $modelName = 'CategoryStruct';
	var $primaryKey = 'id';
	var $updateFields = array('created', 'created_by', 'enable', 'extendable_id', 'is_inherited', 'items_per_page', 'meta_class_id', 'name', 'page_url', 'parent_id', 'sortorder', 'template_id', 'type_id', 'updated', 'updated_by');
	function CategoryDAO() {
		$this->tableName = DatabaseManager::getTable('categories');
	}
	
	function getAllExcept($category_array) {
		$category_string = implode(',', $category_array);
		
		$sql_select = "
			SELECT * 
			FROM " . DatabaseManager::getTable('categories');
		if (!empty($category_string)) {
			$sql_select .= "
				WHERE id NOT IN (" . $category_string . ")";
		}
		return $this->selectQuery($sql_select, $this->modelName);
	}
	
	function getCategories() {
		$sql_select = "
			SELECT * 
			FROM " . $this->tableName . "
			WHERE enable = 1
			ORDER BY name";
		return $this->selectQuery($sql_select, $this->modelName);
	}
	
	function getCategoriesByParentId($category_id) {
		$sql_select = "
			SELECT * 
			FROM " . $this->tableName . "
			WHERE enable = 1 AND parent_id = '" . $category_id . "'
			ORDER BY sortorder";
		return $this->selectQuery($sql_select, $this->modelName);
	}
	function getAllCategoriesByParentId($category_id) {
		$sql_select = "
			SELECT * 
			FROM " . $this->tableName . "
			WHERE parent_id = '" . $category_id . "'
			ORDER BY sortorder";
		return $this->selectQuery($sql_select, $this->modelName);
	}
}

?>