<?php

FrameworkManager::loadStruct('custodian');	

class CustodianDAO extends DataAccessObject {
	var $updateFields = array('created', 'created_by', 'hostname', 'link', 'location', 'message', 'remote_ip', 'referrer', 'severity', 'type', 'variables');
	var $modelName = 'CustodianStruct';
	function __construct() {
		$this->tableName = DatabaseManager::getTable('custodian');
	}
	
	public function getRecords($type=null, $severity=null, $start_date=null, $end_date=null) {
		
		$sql_select = "
			SELECT *
			FROM `" . $this->tableName . "`";
		
		$where = array();
		if (!is_null($type))		$where[] = "`type` = '" . $this->safeString($type) . "'";
		if (!is_null($severity))	$where[] = "severity = '" . $this->safeString($severity) . "'";
		if (!is_null($start_date))	$where[] = "start_date >= '" . $this->safeString($start_date) . "'";
		if (!is_null($end_date))	$where[] = "end_date <= '" . $this->safeString($end_date) . "'";
		
		if (count($where) > 0) {
			$sql_select .= "
				WHERE " . implode(' AND ', $where);
		}
		$sql_select .= "
			ORDER BY created DESC, id DESC";
		
		return $this->selectQuery($sql_select, $this->modelName);
			
	}
	
	public function clearTable() {
		$this->commandQuery("TRUNCATE `" . $this->tableName . "`");
	}
}

?>