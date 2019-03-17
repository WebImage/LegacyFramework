<?php
/**
 * DataAccessObject for AssetTypes
 * 
 * @author Robert Jones II <support@corporatewebimage.com>
 * @copyright Copyright (c) 2007 Corporate Web Image, Inc.
 * @package DataAccessObject
 * @version 1.0 (10/01/2007), Athena_v1.0
 */

// Load required data structures
FrameworkManager::loadStruct('assettype');

class AssetTypeDAO extends DataAccessObject {
	var $modelName = 'AssetTypeStruct';
	function __construct() {
		$this->tableName = DatabaseManager::getTable('asset_types');
	}
	function getAssetTypeByName($name) {
		$sql_select = "
			SELECT * 
			FROM `" . $this->tableName . "`
			WHERE name = '" . $this->safeString($name) . "'";
		$query = $this->selectQuery($sql_select);
		return $query->getAt(0);
	}
}

?>