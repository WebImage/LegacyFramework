<?php

/**
 * DataAccessObject for FormFields
 * 
 * @author Robert Jones II 
 * @copyright Copyright (c) 2007 Corporate Web Image, Inc.
 * @package DataAccessObject
 * @version 1.0 (05/25/2012), Athena_v1.0
 */

// Load required data structures
FrameworkManager::loadStruct('formfield');

class FormFieldDAO extends DataAccessObject {
	var $modelName = 'FormFieldStruct';
	var $updateFields = array('choices','config','created','created_by','enable','key','label','sortorder','type_id','updated','updated_by');
	var $primaryKey = array('field_id', 'form_id');
	public function __construct() {
		$this->tableName = DatabaseManager::getTable('form_fields');
	}
	
	public function getFormFieldsByFormId($form_id) {
		$sql_select = "
			SELECT *
			FROM `" . $this->tableName . "`
			WHERE 
				enable = 1 AND
				form_id = '" . $this->safeString($form_id) . "'
			ORDER BY sortorder";
		return $this->selectQuery($sql_select, $this->modelName);
	}
	public function getFormFieldByFormIdAndFieldId($form_id, $field_id) {
		$sql_select = "
			SELECT *
			FROM `" . $this->tableName . "`
			WHERE 
				form_id = '" . $this->safeString($form_id) . "' AND 
				field_id = '" . $this->safeString($field_id) . "'";
		return $this->selectQuery($sql_select, $this->modelName)->getAt(0);
	}
	public function getMaxFieldIdForForm($form_id) {
		$sql_select = "
			SELECT (COALESCE(MAX(field_id),0)) AS field_id
			FROM `" . $this->tableName . "`
			WHERE form_id = '" . $this->safeString($form_id) . "'
			ORDER BY sortorder";
		
		$record = $this->selectQuery($sql_select)->getAt(0);
		return $record->field_id;
	}
	
	public function getMaxSortOrderForForm($form_id) {
		$sql_select = "
			SELECT (COALESCE(MAX(sortorder),0)) AS field_id
			FROM `" . $this->tableName . "`
			WHERE 
				enable = 1 AND 
				form_id = '" . $this->safeString($form_id) . "'
			ORDER BY sortorder";
		$record = $this->selectQuery($sql_select)->getAt(0);
		return $record->field_id;
	}
	
}

?>