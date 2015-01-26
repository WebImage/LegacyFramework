<?php
/**
 * DataAccessObject for Sites
 * 
 * @author Robert Jones II <support@corporatewebimage.com>
 * @copyright Copyright (c) 2007 Corporate Web Image, Inc.
 * @package DataAccessObject
 * @version 1.0 (10/01/2007), Athena_v1.0
 */

// Load required data structures
FrameworkManager::loadStruct('site');

class SiteDAO extends DataAccessObject {
	var $modelName = 'SiteStruct';
	var $updateFields = array('company_id', 'created', 'created_by', 'domain', 'enable', 'environment', 'is_remote', 'key', 'name', 'parent_id', 'updated', 'updated_by');
	
	public function SiteDAO() {
		$this->tableName = DatabaseManager::getTable('sites');
	}
	public function getAllSites() {
		$sql_select = "
			SELECT *
			FROM `" . $this->tableName . "`
			ORDER BY name";
		return $this->selectQuery($sql_select, $this->modelName);
	}
	public function getSiteByDomain($domain_name) {
		$sql_select = "
			SELECT *
			FROM `" . $this->tableName . "`
			WHERE domain = '" . $domain_name . "'";
		$query = $this->selectQuery($sql_select, $this->modelName);
		return $query->getAt(0);
	}
	
	public function getSitesByParentId($site_id) {
		$sql_select = "
			SELECT *
			FROM `" . $this->tableName . "`
			WHERE parent_id = '" . $this->safeString($site_id) . "'
			ORDER BY name";
		return $this->selectQuery($sql_select, $this->modelName);
	}
	
	public function getSitesByCompanyId($company_id) {
		$sql_select = "
			SELECT *
			FROM `" . $this->tableName . "`
			WHERE company_id = '" . $this->safeString($company_id) . "'";
		return $this->selectQuery($sql_select, $this->modelName);
	}
}

?>