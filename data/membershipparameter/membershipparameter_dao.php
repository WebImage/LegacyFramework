<?php
/**
 * DataAccessObject for MembershipParameters
 * 
 * @author Robert Jones II <support@corporatewebimage.com>
 * @copyright Copyright (c) 2007 Corporate Web Image, Inc.
 * @package DataAccessObject
 * @version 1.0 (10/01/2007), Athena_v1.0
 */

// Load required data structures
FrameworkManager::loadStruct('membershipparameter');

class MembershipParameterDAO extends DataAccessObject {
	var $modelName = 'MembershipParameterStruct';
	var $updateFields = array('created', 'created_by', 'membership_id', 'parameter', 'updated', 'updated_by', 'value');
	
	function MembershipParameterDAO() {
		$this->tableName = DatabaseManager::getTable('membership_parameters');
	}
	
	function getParametersByMembershipId($membership_id) {
		$sql_select = "
			SELECT *
			FROM " . $this->tableName . " 
			WHERE membership_id = '" . $membership_id . "'";
		return $this->selectQuery($sql_select, $this->modelName);
	}
	
	function getParameterByMembershipIdAndParameter($membership_id, $parameter) {
		$sql_select = "
			SELECT *
			FROM " . $this->tableName . " 
			WHERE 
				membership_id = '" . $membership_id . "' AND
				parameter = '" . $parameter . "'";
		$query = $this->selectQuery($sql_select, $this->modelName);
		return $query->getAt(0);
	}
}
	
?>