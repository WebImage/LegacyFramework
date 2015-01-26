<?php
FrameworkManager::loadDAO('site');

class SiteLogic {
	public static function getSiteByDomain($domain_name) {
		$site_dao = new SiteDAO();
		return $site_dao->getSiteByDomain($domain_name);
	}
	
	public static function getSiteIdByDomain($domain_name) {
		if ($site = SiteLogic::getSiteByDomain($domain_name)) {
			return $site->id;
		}
		return false;
	}
	
	public static function getSitesByParentId($site_id) {
		$dao = new SiteDAO();
		return $dao->getSitesByParentId($site_id);
	}
	
	public static function getSiteAliasesByDomain($domain) {
		$rs = new ResultSet();
		if ($site_id = SiteLogic::getSiteIdByDomain($domain)) {
			return SiteLogic::getSitesByParentId($site_id);
		}
		return $rs;
	}
	
	public static function getAllSites() {
		$site_dao = new SiteDAO();
		$sites = $site_dao->loadAll();
		return $site_dao->getAllSites();
	}
	public static function getSiteById($site_id) {
		$site_dao = new SiteDAO();
		return $site_dao->load($site_id);
	}
	public static function save($site_struct) {
		// Make sure parent_id always has a value
		if (strlen($site_struct->parent_id) == 0) {
			$site_struct->parent_id = 0;
		}
		
		$site_dao = new SiteDAO();
		return $site_dao->save($site_struct);
	}
}

?>