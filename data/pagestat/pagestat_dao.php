<?php

FrameworkManager::loadStruct('pagestat');
class PageStatDAO extends DataAccessObject {
	var $updateFields = array('browser', 'browser_majver', 'browser_minver', 'created', 'created_by', 'domain', 'enable', 'ip_address', 'is_crawler', 'is_entry', 'is_secure', 'is_exit', 'js_enabled', 'last_checkin', 'loc_lat', 'loc_long', 'loc_city', 'loc_state', 'loc_country', 'membership_id', 'os', 'page_id', 'path', 'protocol', 'query', 'process_status', 'referrer', 'request_handler', 'resolution', 'session_id', 'updated', 'updated_by', 'user_agent');
	var $modelName = 'PageStatStruct';
	function __construct() {
		$this->tableName = DatabaseManager::getTable('page_stats');
	}
	/*
	public function getPageStatsWithUnprocessedBrowser() {
		$sql_select = "
			SELECT * 
			FROM `" . $this->tableName . "`
			WHERE browser = -1";
		return $this->selectQuery($sql_select, $this->modelName);
	}
	*/
	
	public function getGroupedUserAgentsForUnprocessedBrowsers() {
		$sql_select = "
			SELECT user_agent
			FROM `" . $this->tableName . "`
			WHERE browser = '-1'
			GROUP BY user_agent";
		return $this->selectQuery($sql_select, $this->modelName);
	}

	public function getPageViewsByDay($start_date, $end_date) {
		if (preg_match('/^[0-9]{4}-[0-9]{2}-[0-9]{2}$/', $start_date)) $start_date = $start_date .= ' 00:00:00';
		if (preg_match('/^[0-9]{4}-[0-9]{2}-[0-9]{2}$/', $end_date)) $end_date = $end_date .= ' 23:59:59';
		
		$sql_select = "
			SELECT 
				CONCAT(YEAR(created),'-',LPAD(MONTH(created), 2, '0'),'-',LPAD(DAY(created),2,'0')) AS day,
				COUNT(*) AS page_views 
			FROM `" . $this->tableName . "` 
			WHERE is_crawler = 0 AND created >= '" . $this->safeString($start_date) . "' AND created <= '" . $this->safeString($end_date) . "'
			GROUP BY day 
			ORDER BY day";

		return $this->selectQuery($sql_select);
	}
	
	public function getTopPageViewsForPeriod($start_date, $end_date) {
		if (preg_match('/^[0-9]{4}-[0-9]{2}-[0-9]{2}$/', $start_date)) $start_date = $start_date .= ' 00:00:00';
		if (preg_match('/^[0-9]{4}-[0-9]{2}-[0-9]{2}$/', $end_date)) $end_date = $end_date .= ' 23:59:59';
		$sql_select = "
			SELECT CONCAT(path,' - ', page_id) AS label, COUNT(path) AS cnt
			FROM `" . $this->tableName . "` page_stats 
			WHERE 
				is_crawler = 0 AND 
				created >= '" . $this->safeString($start_date) . "' AND 
				created <= '" . $this->safeString($end_date) . "'			
			 GROUP BY path 
			 ORDER BY cnt DESC";

		return $this->selectQuery($sql_select);
	}
	
	public function getTopBrowsersForPeriod($start_date, $end_date) {
		if (preg_match('/^[0-9]{4}-[0-9]{2}-[0-9]{2}$/', $start_date)) $start_date = $start_date .= ' 00:00:00';
		if (preg_match('/^[0-9]{4}-[0-9]{2}-[0-9]{2}$/', $end_date)) $end_date = $end_date .= ' 23:59:59';
		$sql_select = "
			SELECT browser as label, COUNT(browser) AS cnt
			FROM `" . $this->tableName . "` page_stats 
			WHERE 
				is_crawler = 0 AND
				created >= '" . $this->safeString($start_date) . "' AND 
				created <= '" . $this->safeString($end_date) . "'
			GROUP BY browser 
			ORDER BY cnt DESC";
		return $this->selectQuery($sql_select);
	}
	
	
	public function setUnprocessedBrowsersByUserAgent($page_stat_struct) {
		$sql_command = "UPDATE `" . $this->tableName . "` SET ";
		$sql_command .= "browser = '" . $this->safeString($page_stat_struct->browser) . "', ";
		$sql_command .= "browser_majver = '" . $this->safeString($page_stat_struct->browser_majver) . "', ";
		$sql_command .= "browser_minver = '" . $this->safeString($page_stat_struct->browser_minver) . "', ";
		$sql_command .= "is_crawler = '" . $this->safeString($page_stat_struct->is_crawler) . "', ";
		$sql_command .= "os = '" . $this->safeString($page_stat_struct->os) . "' ";
		$sql_command .= "WHERE browser = '-1' AND user_agent = '" . $this->safeString($page_stat_struct->user_agent) . "'";
		
		return $this->commandQuery($sql_command);
		
	}
}

?>