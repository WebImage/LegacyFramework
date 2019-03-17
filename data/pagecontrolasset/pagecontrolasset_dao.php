<?php
/**
 * DataAccessObject for PageControlAssets
 * 
 * @author Robert Jones II <support@corporatewebimage.com>
 * @copyright Copyright (c) 2007 Corporate Web Image, Inc.
 * @package DataAccessObject
 * @version 1.0 (10/01/2007), Athena_v1.0
 */

// Load required data structures
FrameworkManager::loadStruct('pagecontrolasset');

class PageControlAssetDAO extends DataAccessObject {
	var $modelName = 'PageControlAssetStruct';
	var $tableName;
	var $primaryKey = 'id';
	var $updateFields = array('asset_id', 'created', 'created_by', 'page_control_id', 'updated', 'updated_by');
	
	function __construct() {
		$this->tableName = DatabaseManager::getTable('page_control_assets');
	}
	
	function getAssetsByPageControlId($page_control_id) {
		$select_sql = "
			SELECT 
				page_control_assets.asset_id, page_control_assets.id, page_control_assets.page_control_id,
				assets.config, assets.file_src, assets.height, assets.id AS asset_id, assets.width
			FROM " . $this->tableName . " page_control_assets
				LEFT JOIN " . DatabaseManager::getTable('assets') . " assets ON assets.id = page_control_assets.asset_id
			WHERE page_control_assets.page_control_id = '" . $page_control_id . "'";
		$results = $this->selectQuery($select_sql, $this->modelName);
		
		return $results;
	}
}

?>