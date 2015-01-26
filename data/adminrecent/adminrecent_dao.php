<?php

/**
 * DataAccessObject for AdminRecentDAO
 * 
 * @author Robert Jones II <support@corporatewebimage.com>
 * @copyright Copyright (c) 2007 Corporate Web Image, Inc.
 * @package DataAccessObject
 * @version 1.0 (06/28/2014), Athena_v1.0
 */

// Load required data structures
FrameworkManager::loadStruct('adminrecent');

class AdminRecentDAO extends DataAccessObject {
	var $modelName = 'AdminRecentStruct';
	var $updateFields = array('created','created_by','membership_id','name','type','updated','updated_by','url');
	var $primaryKey = 'id';
	public function __construct() {
		$this->tableName = DatabaseManager::getTable('admin_recent');
	}
	
	/**
	 * @param array $types An array of types to lookup
	 * @param string $membership_id
	 * @return ResultSet
	 */
	public function getAdminRecent($types=null, $membership_id=null) {
		
		$sql = "
			SELECT MAX(created) AS created, MAX(id) AS id, membership_id, name, `type`, url
			FROM `" . $this->tableName . "`
			WHERE 1=1";
		
		if (null !== $membership_id) $sql .= " AND membership_id = '" . $membership_id . "'";
		
		if (null !== $types) {
			if (!is_array($types)) $types = array($types);
			$sql .= " AND `type` IN ('" . implode("','", $types) . "')";
		}
		
		$sql .= "
			GROUP BY `type`, url, name, membership_id
			ORDER BY created DESC";
		
		return $this->selectQuery($sql, $this->modelName);
		
	}
	
}

?>