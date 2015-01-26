<?php
/**
 * DataAccessObject for Pages
 * 
 * @author Robert Jones II <support@corporatewebimage.com>
 * @copyright Copyright (c) 2007 Corporate Web Image, Inc.
 * @package DataAccessObject
 * @version 1.0 (10/01/2007), Athena_v1.0
 *
 * CHANGES
 * 06/18/2009	(Robert Jones) Changed all page queries to incorporate page_left and page_right
 */

// Load required data structures

FrameworkManager::loadStruct('page');

class PageDAO extends DataAccessObject {
	var $modelName = 'PageStruct';
	//var $tableName = TABLE_PAGES;
	var $primaryKey = 'id';
	var $updateFields = array('created', 'created_by', 'is_section', 'is_secure', 'meta_key', 'meta_desc', 'page_left', 'page_right', 'page_url', 'parent_id', 'service_handler_class', 'short_title', 'status', 'template_id', 'title', 'type', 'updated', 'updated_by');
	function __construct() {
		$this->tableName = DatabaseManager::getTable('pages');
	}
	
	public function getNumPages() {
		$select_sql = "
			SELECT COUNT(*) AS total
			FROM `" . $this->tableName . "`
			WHERE enable = 1 AND `type` = 'S'";
		$results = $this->selectQuery($select_sql);
		$query = $results->getAt(0);
		return $query->total;
	}
	
	public function getPages() {
		
		$select_sql = "
			SELECT *
			FROM `" . $this->tableName . "`
			WHERE enable = 1 AND `type` = 'S'";
		return $this->selectQuery($select_sql);
	}
	
	public function getPageById($page_id) {
		$select_sql = "
			SELECT 
				pages.id, pages.is_section, pages.meta_desc, pages.meta_key, pages.page_left, pages.page_right, pages.page_url, pages.parent_id, pages.service_handler_class, pages.template_id, pages.title, pages.`type`,
				templates.file_contents AS template_contents, templates.file_src as template_src, templates.name AS template_name
			FROM " . $this->tableName . " pages
				LEFT JOIN " . DatabaseManager::getTable('templates') . "  templates ON templates.id = pages.template_id
			WHERE pages.id = '" . $page_id . "' AND pages.enable = 1";
		$results = $this->selectQuery($select_sql, $this->modelName);
		return $results->getAt(0);
	}
	
	public function getPageSectionsByParentId($parent_id) {
		$select_sql = "
			SELECT 
				pages.id, pages.is_section, pages.meta_desc, pages.meta_key, pages.page_left, pages.page_right, pages.page_url, pages.parent_id, pages.service_handler_class, pages.template_id, pages.title, pages.`type`,
				templates.file_src as template_src, templates.name AS template_name
			FROM " . $this->tableName . " pages
				LEFT JOIN " . DatabaseManager::getTable('templates') . "  templates ON templates.id = pages.template_id
			WHERE pages.parent_id = '" . $parent_id . "' AND pages.enable = 1 AND pages.is_section = 1 AND pages.`type` = 'S'
			ORDER BY pages.title";
		return $this->selectQuery($select_sql, $this->modelName);
	}
	
	public function getPagesByParentId($parent_id) {
		$select_sql = "
			SELECT
				pages.id, pages.is_section, pages.meta_desc, pages.meta_key, pages.page_left, pages.page_right, pages.page_url, pages.parent_id, pages.service_handler_class, pages.template_id, pages.title, pages.`type`,
				templates.file_src as template_src, templates.name AS template_name
			FROM " . $this->tableName . " pages
				LEFT JOIN " . DatabaseManager::getTable('templates') . "  templates ON templates.id = pages.template_id
			WHERE pages.parent_id = '" . $parent_id . "' AND pages.enable = 1 AND pages.`type` = 'S'
			ORDER BY pages.title";
		return $this->selectQuery($select_sql, $this->modelName);
	}
	
	public function getPageByUrl($url) {
		$select_sql = "
			SELECT 
				pages.id, pages.is_section, pages.is_secure, pages.meta_desc, pages.meta_key, pages.page_left, pages.page_right, pages.page_url, pages.parent_id, pages.service_handler_class, pages.template_id, pages.title, pages.`type`,
				templates.file_contents AS template_contents, templates.file_src as template_src, templates.name AS template_name
			FROM " . $this->tableName . " pages
				LEFT JOIN " . DatabaseManager::getTable('templates') . "  templates ON templates.id = pages.template_id
			WHERE pages.page_url = '" . $url . "' AND pages.enable = 1";
		$results = $this->selectQuery($select_sql, $this->modelName);
		return $results->getAt(0);
	}
	
	public function getSections() {
		$select_sql = "
			SELECT 
				pages.id, pages.is_section, pages.is_secure, pages.meta_desc, pages.meta_key, pages.page_left, pages.page_right, pages.page_url, pages.parent_id, pages.service_handler_class, pages.template_id, pages.title, pages.`type`,
				templates.file_src as template_src, templates.name AS template_name
			FROM " . $this->tableName . " pages
				LEFT JOIN " . DatabaseManager::getTable('templates') . "  templates ON templates.id = pages.template_id
			WHERE pages.is_section = 1 AND pages.enable = 1 AND pages.`type` = 'S'";
		$results = $this->selectQuery($select_sql, $this->modelName);
		return $results;
	}
	
	/** 
	 * Override standard save to see if hierarchy needs to be tweaked
	 */
	public function save($page_struct) {
		if ($this->isNewRecord($page_struct)) {
			// Create room under parent
			if (!empty($page_struct->parent_id)) {
				$page_left = $this->createSpaceForNewNode($page_struct->parent_id);
				$page_struct->page_left = $page_left;
				$page_struct->page_right = $page_left + 1;
			}
		} else {
			// Check if parent_id has changed, update order if necessary
			$existing_page = $this->load($page_struct->id);
			if (!empty($page_struct->parent_id) && $page_struct->parent_id != $existing_page->parent_id) {
				
				$existing_page_left = $existing_page->page_left;
				$existing_page_right = $existing_page->page_right;
				
				$node_width = $existing_page->page_right - $existing_page->page_left + 1;
				// 1. Create Gap - makes space for the position that the moved nodes will occupy
				$start_new_left = $this->createSpaceForNewNode($page_struct->parent_id, $node_width);

				// Find the difference between new location and existing so that it can be shifted accordingly
				
				// Adjust shift direction by node width
				if ($existing_page_left > $start_new_left) {
					$existing_page_left += $node_width;
					$existing_page_right += $node_width;
				}
				
				$shift_direction_distance = $start_new_left - $existing_page_left;
				
				// 2. Move Desired Nodes			
				$this->commandQuery("UPDATE `" . $this->tableName . "` SET page_left = page_left + " . $shift_direction_distance . " WHERE page_left BETWEEN " . $existing_page_left . " AND " . $existing_page_right);
				$this->commandQuery("UPDATE `" . $this->tableName . "` SET page_right = page_right + " . $shift_direction_distance . " WHERE page_right BETWEEN " . $existing_page_left . " AND " . $existing_page_right);

				//3. Close Gap
				$this->commandQuery("UPDATE `" . $this->tableName . "` SET page_left = page_left - " . $node_width . " WHERE page_left >= " . $existing_page_left);
				$this->commandQuery("UPDATE `" . $this->tableName . "` SET page_right = page_right - " . $node_width . " WHERE page_right >= " . $existing_page_left);
				
			}
		}
		// Need to update left and right...
		return parent::save($page_struct);
	}
	
	/**
	 * Create space in the existing hierarchy by shifting left and right points of existing hierarchy
	 */
	public function createSpaceForNewNode($parent_id, $slots_to_create=1) {

		if (!is_numeric($slots_to_create)) $slots_to_create = 1;
		if ($slots_to_create < 2)
			$space_to_add = 2;
		else 
			$space_to_add = $slots_to_create; // * 2; // Each node occupies at least two digits
		
		
		$this->commandQuery("LOCK TABLE `" . $this->tableName . "` WRITE");
		
		$query = $this->selectQuery("SELECT page_right FROM `" . $this->tableName . "` WHERE id = '" . $this->safeString($parent_id) . "';");
		$page_right_query = $query->getAt(0);
		$page_right = $page_right_query->page_right;
		
		$this->commandQuery("UPDATE `" . $this->tableName . "` SET page_left = page_left + " . $space_to_add . " WHERE page_left >= " . $page_right . ";");
		$this->commandQuery("UPDATE `" . $this->tableName . "` SET page_right = page_right + " . $space_to_add . " WHERE page_right >= " . $page_right . ";");

		$this->commandQuery("UNLOCK TABLES");
		return $page_right;
	}
	
}

?>