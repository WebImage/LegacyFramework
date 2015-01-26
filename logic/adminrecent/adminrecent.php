<?php

FrameworkManager::loadDAO('adminrecent');
class AdminRecentLogic {
	/**
	 * @param mixed(string|array) $types A single type as a string or an array of types
	 * @param string $membership_id
	 * @param int $start_page The page from which to start results
	 * @param int $results_per_page The number of results to retrieve
	 * @return ResultSet
	 */
	public static function getAdminRecent($types=null, $membership_id=null, $start_page=null, $result_per_page=null) {
		$dao = new AdminRecentDAO();
		if (null !== $start_page || null != $result_per_page) $dao->paginate($start_page, $result_per_page);
		if (!is_array($types)) $types = array($types);
		return $dao->getAdminRecent($types, $membership_id);
	}
	public static function getAdminRecentById($id) {
		$dao = new AdminRecentDAO();
		return $dao->load($id);
	}
	public static function save(AdminRecentStruct $struct) {
		$dao = new AdminRecentDAO();
		return $dao->save($struct);
	}
	
	public static function create($type, $name, $url, $membership_id) {
		
		$struct = new AdminRecentStruct();
		$struct->type = $type;
		$struct->name = $name;
		$struct->url = $url;
		$struct->membership_id = $membership_id;
		
		$dao = new AdminRecentDAO();
		return $dao->save($struct);
	}
}

?>