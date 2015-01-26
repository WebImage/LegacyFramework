<?php

/**
 * DataAccessObject for FormEntries
 * 
 * @author Robert Jones II 
 * @copyright Copyright (c) 2007 Corporate Web Image, Inc.
 * @package DataAccessObject
 * @version 1.0 (05/24/2012), Athena_v1.0
 */

// Load required data structures
FrameworkManager::loadStruct('formentry');

class FormEntryDAO extends DataAccessObject {
	var $modelName = 'FormEntryStruct';
	var $updateFields = array('created','created_by','form_id','ip','page_id','page_url','page_referrer','read','stat_id','updated','updated_by');
	
	function __construct() {
		$this->tableName = DatabaseManager::getTable('form_entries');
	}
	
	public function getFormEntriesByFormId($form_id) {
		
		$sql_select = "
			SELECT *
			FROM `" . $this->tableName . "`
			WHERE form_id = '" . $this->safeString($form_id) . "'";
			
		return $this->selectQuery($sql_select);
		
	}
}

?>