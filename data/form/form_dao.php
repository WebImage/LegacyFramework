<?php

/**
 * DataAccessObject for Forms
 * 
 * @author Robert Jones II 
 * @copyright Copyright (c) 2007 Corporate Web Image, Inc.
 * @package DataAccessObject
 * @version 1.0 (05/24/2012), Athena_v1.0
 */

// Load required data structures
FrameworkManager::loadStruct('form');

class FormDAO extends DataAccessObject {
	var $modelName = 'FormStruct';
	var $updateFields = array('created','created_by','config','enable','name','updated','updated_by');
	function __construct() {
		$this->tableName = DatabaseManager::getTable('forms');
	}
}

?>