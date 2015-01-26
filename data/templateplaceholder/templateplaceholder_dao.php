<?php
/**
 * DataAccessObject for TemplatePlaceholders
 * 
 * @author Robert Jones II <support@corporatewebimage.com>
 * @copyright Copyright (c) 2007 Corporate Web Image, Inc.
 * @package DataAccessObject
 * @version 1.0 (10/01/2007), Athena_v1.0
 */

// Load required data structures
FrameworkManager::loadStruct('templateplaceholder');

class TemplatePlaceholderDAO extends DataAccessObject {
	var $modelName = 'TemplatePlaceholderStruct';
	//var $tableName = TABLE_TEMPLATE_PLACEHOLDERS;
	var $primaryKey = 'id';
	
	function TemplatePlaceholderDAO() {
		$this->tableName = DatabaseManager::getTable('template_placeholders');
	}
}

?>