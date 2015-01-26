<?php
FrameworkManager::loadDAO('pagecontrolasset');
class PageControlAssetLogic {
	public static function getAssetsByPageControlId($page_control_id) {
		// Data Access Object
		$dao = new PageControlAssetDAO();
		return $dao->getAssetsByPageControlId($page_control_id);
	}
}

?>