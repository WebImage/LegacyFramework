<?php
/**
 * DataAccessObject for PageParameters
 * 
 * @author Robert Jones II <support@corporatewebimage.com>
 * @copyright Copyright (c) 2007 Corporate Web Image, Inc.
 * @package DataAccessObject
 * @version 1.0 (10/01/2007), Athena_v1.0
 */

// Load required data structures
FrameworkManager::loadStruct('pageparameter');

class PageParameterDAO extends DataAccessObject {
	var $modelName = 'PageParameterStruct';
	var $updateFields = array('created', 'created_by', 'parameter', 'page_id', 'updated', 'updated_by', 'value');
	
	function PageParameterDAO() {
		$this->tableName = DatabaseManager::getTable('page_parameters');
	}
	
	function getPageParametersByPageId($page_id) {
		$sql_select = "
			SELECT * 
			FROM " . $this->tableName . "
			WHERE page_id = '" . $page_id . "'";
		return $this->selectQuery($sql_select, $this->modelName);
	}
}

?>