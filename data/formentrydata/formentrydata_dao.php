<?php

/**
 * DataAccessObject for FormEntryData
 * 
 * @author Robert Jones II 
 * @copyright Copyright (c) 2007 Corporate Web Image, Inc.
 * @package DataAccessObject
 * @version 1.0 (05/24/2012), Athena_v1.0
 */

// Load required data structures
FrameworkManager::loadStruct('formentrydata');

class FormEntryDataDAO extends DataAccessObject {
	var $modelName = 'FormEntryDataStruct';
	var $updateFields = array('form_entry_id', 'field_id', 'updated','updated_by','value');
	
	function __construct() {
		$this->tableName = DatabaseManager::getTable('form_entry_data');
	}
	
	public function getDataByEntryIds(array $entry_ids) {
		
		$sql_select = "
			SELECT *
			FROM `" . $this->tableName . "`
			WHERE form_entry_id IN ('" . implode("', '", $entry_ids) . "')
			ORDER BY form_entry_id, field_id";
			
		return $this->selectQuery($sql_select, $this->modelName);
		
	}
}

?>