<?php

/**
 * 10/10/2012	(Robert Jones) Removed "page_key" from PageStruct object 
 **/
FrameworkManager::loadDAO('page');
class PageLogic {
	const STATUS_DRAFT = 'draft';
	const STATUS_DELETED = 'deleted';
	const STATUS_ARCHIVED = 'archived';
	const STATUS_PUBLISHED = 'published';
	
	const TYPE_SINGLE = 'S';
	const TYPE_GROUP = 'G';
	const TYPE_LINK = 'L';
	const TYPE_CUSTOM = 'C';
	const TYPE_FILE = 'F';// File exists within the framework's /pages/ directory - not yet used
	
	public static function getNumPages() { 
		$page_dao = new PageDAO();
		return $page_dao->getNumPages();
	}
	
	public static function getPages() {
		$page_dao = new PageDAO();
		return $page_dao->getPages();
	}
	
	public static function getPageById($id) {
		$page_dao = new PageDAO();
		return $page_dao->getPageById($id);
	}
	public static function getPageByUrl($url) {
		$page_dao = new PageDAO();
		return $page_dao->getPageByUrl($url);
	}
	
	public static function getPageSectionsByParentId($parent_id) {
		$page_dao = new PageDAO();
		return $page_dao->getPageSectionsByParentId($parent_id);
	}
	
	public static function getPagesByParentId($parent_id) {
		$page_dao = new PageDAO();
		return $page_dao->getPagesByParentId($parent_id);
	}
	
	public static function getFlatSectionHierarchy($parent_id=0, $level=1) {
		$pages = PageLogic::getPageSectionsByParentId($parent_id);
		
		$results = new ResultSet();
		
		while ($page = $pages->getNext()) {

			$page->level = $level;
			
			if ($page->page_url == '/index.html') {
				$results->insert($page);
			} else {
				$results->add($page);
			}
			
			if ($page->is_section == 1) {
				$children = PageLogic::getFlatSectionHierarchy($page->id, $level+1);
				while ($child = $children->getNext()) {
					#$child->level = $level+1;
					$results->add($child);
				}
			}
		}
		
		return $results;
	}
	
	public static function getFlatPageHierarchy($parent_id=0, $level=1) {
		$pages = PageLogic::getPagesByParentId($parent_id);

		$results = new ResultSet();
		while ($page = $pages->getNext()) {
			$page->level = $level;
			
			if ($page->page_url == '/index.html') {
				$results->insert($page);
			} else {
				$results->add($page);
			}
			
			if ($page->is_section == 1) {
				$children = PageLogic::getFlatPageHierarchy($page->id, $level+1);
				while ($child = $children->getNext()) {
					#$child->level = $level+1;
					$results->add($child);
				}
			}
		}
		
		return $results;
	}
	
	public static function getSections() {
		$page_dao = new PageDAO();
		return $page_dao->getSections();
	}
	
	public static function getAllPages() {
		$page_dao = new PageDAO();
		return $page_dao->loadAll();
	}
	

	public static function save($page_struct) {
		$page_dao = new PageDAO();
		return $page_dao->save($page_struct);
	}
	
	#public static function createQuickPage($title, $key, $status=PageLogic::STATUS_PUBLISHED, $parent_id=0, $template_id=null, $page_url='', $page_type='S', $service_handler_class='', $is_section=false) {
	public static function createQuickPage($title, $status=PageLogic::STATUS_PUBLISHED, $parent_id=0, $template_id=null, $page_url='', $page_type='S', $service_handler_class='', $is_section=false) {
	#function createRequestHandlerPage($title, $key, $status=PageLogic::STATUS_PUBLISHED, $template_id=null) {
		FrameworkManager::loadStruct('page');
				
		$page = new PageStruct();
		$page->is_section = ($is_section ? 1 : 0);
		$page->page_url = $page_url;
		$page->parent_id = $parent_id;
		$page->service_handler_class = $service_handler_class;
		$page->status = $status;
		$page->template_id = $template_id;
		$page->type = $page_type;
		$page->title = $title;
		return PageLogic::save($page);
	}
	
	public static function createQuickSection($title, $status=PageLogic::STATUS_PUBLISHED, $parent_id=0, $template_id=null, $page_url='', $page_type='S', $service_handler_class=0) {
		$is_section = true;
		return PageLogic::createQuickPage($title, $status, $parent_id, $template_id, $page_url, $page_type, $service_handler_class, $is_section);
	}
	
	public static function createHomePage() {
		if ($page = PageLogic::getPageByUrl('/index.html')) {
			return $page;
		} else {
			FrameworkManager::loadStruct('page');
			$page = new PageStruct();
			$page->enable = 1;
			$page->is_section = 1;
			$page->page_url = '/index.html';
			$page->parent_id = 0;
			$page->status = PageLogic::STATUS_PUBLISHED;
			$page->template_id = 1;
			$page->type = PageLogic::TYPE_SINGLE;
			$page->title = 'Home Page';
			return PageLogic::save($page);
		}
	}
}

?>