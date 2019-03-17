<?php
/**
 * DataAccessObject for Content
 * 
 * @author Robert Jones II <support@corporatewebimage.com>
 * @copyright Copyright (c) 2007 Corporate Web Image, Inc.
 * @package DataAccessObject
 * @version 1.0 (10/01/2007), Athena_v1.0
 */

// Load required data structures
FrameworkManager::loadStruct('content');

class ContentDAO extends DataAccessObject {
	var $modelName = 'ContentStruct';
	var $primaryKey = 'id';
	var $updateFields = array('abstract', 'category_id', 'checked_out', 'checked_out_time', 'created', 'created_by', 'description', 'meta_desc', 'meta_key', 'published', 'publish_start', 'publish_end', 'section_id', 'sort_order', 'title', 'updated', 'updated_by');
		
	function __construct() {
		$this->tableName = DatabaseManager::getTable('content');
	}
}

?>