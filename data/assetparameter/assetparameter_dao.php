<?php

/**
 * DataAccessObject for AssetParameter
 * 
 * @author Robert Jones II 
 * @copyright Copyright (c) 2007 Corporate Web Image, Inc.
 * @package DataAccessObject
 * @version 1.0 (02/17/2010), Athena_v1.0
 */

// Load required data structures
FrameworkManager::loadStruct('assetparameter');

class AssetParameterDAO extends DataAccessObject {
	var $modelName = 'AssetParameterStruct';
	var $updateFields = array('value');
	var $primaryKey = array('asset_id', 'parameter');
	function AssetParameterDAO() {
		$this->tableName = DatabaseManager::getTable('asset_parameters');
	}
	
	function getAssetParametersByAssetId($asset_id) {
		$sql_select = "
			SELECT * 
			FROM `" . $this->tableName . "`
			WHERE 
				asset_id = '" . $this->safeString($asset_id) . "'";
		return $this->selectQuery($sql_select, $this->modelName);
	}
	
	function getAssetParameter($asset_id, $parameter) {
		$sql_select = "
			SELECT * 
			FROM `" . $this->tableName . "`
			WHERE 
				asset_id = '" . $this->safeString($asset_id) . "' AND
				parameter = '" . $this->safeString($parameter) . "'";
		$query = $this->selectQuery($sql_select, $this->modelName);
		return $query->getAt(0);
	}
	
	function setAssetParameter($asset_id, $parameter, $value) {
		$force_insert = $this->isForceInsert();
		if (!$this->getAssetParameter($asset_id, $parameter)) { // Does not exist, force save
			$this->setForceInsert(true);
		}
		$asset_param_struct = new AssetParameterStruct();
		$asset_param_struct->asset_id = $asset_id;
		$asset_param_struct->parameter = $parameter;
		$asset_param_struct->value = $value;
		$this->save($asset_param_struct);;
	}
	function deleteAssetParametersByAssetId($asset_id) {
		$sql_command = "DELETE FROM `" . $this->tableName . "` WHERE asset_id = '" . $this->safeString($asset_id) . "'";
		return $this->commandQuery($sql_command);
	}
}

?>