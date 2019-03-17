<?php
/**
 * DataAccessObject for Templates
 * 
 * @author Robert Jones II <support@corporatewebimage.com>
 * @copyright Copyright (c) 2007 Corporate Web Image, Inc.
 * @package DataAccessObject
 * @version 1.0 (10/01/2007), Athena_v1.0
 */

// Load required data structures
FrameworkManager::loadStruct('template');

class TemplateDAO extends DataAccessObject {
	var $modelName = 'TemplateStruct';
	var $updateFields = array('created', 'created_by', 'name', 'file_contents', 'file_src', 'type', 'updated', 'updated_by');
	
	
	function __construct() {
		$this->tableName = DatabaseManager::getTable('templates');
	}
	
	function getTemplates($type) {
		$sql = "
			SELECT created, created_by, file_contents, file_src, id, name, sortorder, `type`
			FROM `" . $this->tableName . "`
			WHERE `type` = '" . $this->safeString($type) . "'";
		return $this->selectQuery($sql);
	}
	
	function getAllTemplates() {
		$sql = "
			SELECT created, created_by, file_contents, file_src, id, name, sortorder, `type`
			FROM `" . $this->tableName . "`
			ORDER BY `type`";
		return $this->selectQuery($sql);
	}
	
	function getObjectTemplatesByTypeAndObjectId($type, $object_id, $object_tag=null) {
		$sql = "
			SELECT
				t.created, t.created_by, t.file_contents, t.file_src, t.id, t.name, t.sortorder, t.`type`,
				ot.locale, ot.profile, ot.object_id
			FROM `" . $this->tableName . "` t
				INNER JOIN `" . DatabaseManager::getTable('object_templates') . "` ot ON ot.template_id = t.id
			WHERE 
				t.`type` = '" . $this->safeString($type) . "' AND
				ot.object_id = '" . $this->safeString($object_id) . "'";
		if (!empty($object_tag)) $sql .= " ot.object_tag = '" . $this->safeString($object_tag) . "'";
		return $this->selectQuery($sql);
	}
}

?>