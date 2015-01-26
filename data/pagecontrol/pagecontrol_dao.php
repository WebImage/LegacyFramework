<?php
/**
 * DataAccessObject for PageControls
 * 
 * @author Robert Jones II <support@corporatewebimage.com>
 * @copyright Copyright (c) 2007 Corporate Web Image, Inc.
 * @package DataAccessObject
 * @version 1.0 (10/01/2007), Athena_v1.0
 */

// Load required data structures
FrameworkManager::loadStruct('pagecontrol');

class PageControlDAO extends DataAccessObject {
	var $modelName = 'PageControlStruct';
	var $primaryKey = 'id';
	var $updateFields = array('config', 'control_id', 'created', 'created_by', 'favorite', 'is_draft', 'is_favorite', 'mirror_id', 'page_id', 'sortorder', 'placeholder', 'template_id', 'title', 'updated', 'updated_by');
	function PageControlDAO() {
		$this->tableName = DatabaseManager::getTable('page_controls');
	}
	function getNextSortOrder($page_id, $placeholder) {
		$select_sql = "
			SELECT MAX(sortorder) AS sortorder
			FROM " . $this->tableName . "
			WHERE
				page_id = '" . $page_id . "' AND
				placeholder = '" . $placeholder . "'";
		$result = $this->selectQuery($select_sql);
				
		$result = $result->getAt(0);
		if (empty($result->sortorder)) $sortorder = 1;
		else $sortorder = $result->sortorder + 1;
		return $sortorder;
	}
	function getControlsByPageId($page_id, $show_drafts=false) {
		$select_sql = "
			SELECT
				page_controls.config, page_controls.control_id, page_controls.favorite_title, page_controls.id, page_controls.is_favorite, page_controls.mirror_id, page_controls.page_id, page_controls.sortorder, page_controls.placeholder, page_controls.template_id, page_controls.title,
				controls.class_name, controls.file_src as control_src, controls.label AS control_label
			FROM " . $this->tableName . " page_controls
				LEFT JOIN " . DatabaseManager::getTable('controls'). " controls ON controls.id = page_controls.control_id
			WHERE page_controls.page_id = '". $page_id ."'";
		if (!$show_drafts) $select_sql .= " AND page_controls.is_draft = 0";
		$select_sql .= "
			ORDER BY page_controls.sortorder";
		$results = $this->selectQuery($select_sql, $this->modelName);
		
		return $results;
	}
	
	function getControlsByPageIdOrTemplateId($page_id, $template_id) {
		$select_sql = "
			SELECT
				page_controls.config, page_controls.control_id, page_controls.favorite_title, page_controls.id, page_controls.is_favorite, page_controls.mirror_id, page_controls.page_id, page_controls.sortorder, page_controls.placeholder, page_controls.template_id, page_controls.title,
				controls.class_name, controls.file_src as control_src, controls.label AS control_label
			FROM " . $this->tableName . " page_controls
				LEFT JOIN " . DatabaseManager::getTable('controls'). " controls ON controls.id = page_controls.control_id
			WHERE page_controls.page_id = '" . $this->safeString($page_id) . "'";
		
		if (!empty($template_id)) {
			
			$select_sql .= " OR page_controls.template_id = '" . $this->safeString($template_id) . "'";
			
		}
		
		$select_sql .= "
			ORDER BY page_controls.sortorder";
		$results = $this->selectQuery($select_sql, $this->modelName);
		
		$results = $this->selectQuery($select_sql, $this->modelName);
		
		return $results;
	}
	
	function getControlsByTemplateId($template_id, $show_drafts=false) {
		$select_sql = "
			SELECT
				page_controls.config, page_controls.control_id, page_controls.favorite_title, page_controls.id, page_controls.is_favorite, page_controls.mirror_id, page_controls.page_id, page_controls.sortorder, page_controls.placeholder, page_controls.template_id, page_controls.title,
				controls.class_name, controls.file_src as control_src, controls.label AS control_label
			FROM " . $this->tableName . " page_controls
				LEFT JOIN " . DatabaseManager::getTable('controls'). " controls ON controls.id = page_controls.control_id
			WHERE page_controls.template_id = '". $template_id ."'";
		if (!$show_drafts) $select_sql .= " AND page_controls.is_draft = 0";
		$select_sql .= "
			ORDER BY page_controls.sortorder";
		$results = $this->selectQuery($select_sql, $this->modelName);
		
		return $results;
	}
	
	function getPageControlById($id) {
		$select_sql = "
			SELECT
				page_controls.config, page_controls.control_id, page_controls.favorite_title, page_controls.id, page_controls.is_favorite, page_controls.mirror_id, page_controls.page_id, page_controls.sortorder, page_controls.placeholder, page_controls.template_id, page_controls.title,
				controls.class_name, controls.file_src as control_src, controls.label AS control_label
			FROM " . $this->tableName . " page_controls
				INNER JOIN " . DatabaseManager::getTable('controls') . " controls ON controls.id = page_controls.control_id
			WHERE page_controls.id = '". $id ."'";

		$results = $this->selectQuery($select_sql, $this->modelName);
		
		return $results->getAt(0);
	}
	
	function getPageControlBySortOrder($page_id, $placeholder, $sortorder) {
		$select_sql = "
			SELECT
				page_controls.config, page_controls.control_id, page_controls.favorite_title, page_controls.id, page_controls.is_favorite, page_controls.mirror_id, page_controls.page_id, page_controls.sortorder, page_controls.placeholder, page_controls.template_id, page_controls.title,
				controls.class_name, controls.file_src as control_src, controls.label AS control_label
			FROM " . $this->tableName . " page_controls
				INNER JOIN " . DatabaseManager::getTable('controls') . " controls ON controls.id = page_controls.control_id
			WHERE
				page_controls.page_id = '" . $page_id . "' AND
				page_controls.placeholder = '" . $placeholder . "' AND
				page_controls.sortorder = '" . $sortorder . "'";
		$results = $this->selectQuery($select_sql, $this->modelName);
		return $results->getAt(0);
	}
	
	function getFavoritePageControls() {
		$select_sql = "
			SELECT
				page_controls.config, page_controls.control_id, page_controls.favorite_title, page_controls.id, page_controls.is_favorite, page_controls.mirror_id, page_controls.page_id, page_controls.sortorder, page_controls.placeholder, page_controls.title,
				controls.class_name, controls.file_src as control_src, controls.label AS control_label
			FROM `" . $this->tableName . "` page_controls
				INNER JOIN `" . DatabaseManager::getTable('controls') . "` controls ON controls.id = page_controls.control_id
			WHERE
				page_controls.is_favorite = 1
			ORDER BY page_controls.favorite_title";
		return $this->selectQuery($select_sql, $this->modelName);
	}
}

?>