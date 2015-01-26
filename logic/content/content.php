<?php
FrameworkManager::loadDAO('content');
class ContentLogic {
	public static function getContentById($id) {
		$contentDAO = new ContentDAO();
		return $contentDAO->load($id);
	}
	public static function getAllContent() {
		$contentDAO = new ContentDAO();
		return $contentDAO->loadAll();
	}
	
	public static function save($content_structure) {
		$contentDAO = new ContentDAO();
		return $contentDAO->save($content_structure);
	}
}

?>