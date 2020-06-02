<?php

/**
 *	01/07/2009	Modified PageControlLogic::buildControlByPageControlId() to automatically return mirrored page control
 *	08/09/2010	Added support for CWI_CONTROLS_EDITABLE_AbstractEditInPlaceControl - start phasing EditableControl
 */
FrameworkManager::loadDAO('pagecontrol');
FrameworkManager::loadControl('pagecontrol');

class PageControlLogic {

	public static function getAllPageControls() {
		$page_control = new PageControlDAO();
		return $page_control->loadAll();
	}
	
	public static function getNextSortOrder($page_id, $placeholder) {
		$page_control = new PageControlDAO();
		return $page_control->getNextSortOrder($page_id, $placeholder);
	}
	/*
	 * Get controls that match the page id
	 * @param int $page_id the page id to retrieve 
	 */
	public static function getControlsByPageId($page_id) {
		$show_drafts = false;
		$page_controls = new PageControlDAO();
		if (Page::isAdminRequest()) $show_drafts = true;
		return $page_controls->getControlsByPageId($page_id, $show_drafts);
	}
	public static function getControlsByPageIdOrTemplateId($page_id, $template_id) {
		$page_control_dao = new PageControlDAO();
		$page_controls = $page_control_dao->getControlsByPageIdOrTemplateId($page_id, $template_id);

		/** 
		 * Find out if a mirrored control points to a template control in the same result set
		 * If so then the template control will be removed
		 **/
		$return_controls = new ResultSet();
		while ($page_control = $page_controls->getNext()) {
			$include = true;
			if (!empty($page_control->template_id)) {
				$restore_current_index = $page_controls->getCurrentIndex();

				$page_controls->resetIndex();
				while ($check_control = $page_controls->getNext()) {
					if (!empty($check_control->mirror_id)) {
						if ($check_control->mirror_id == $page_control->id) {
							$include = false;
						}
					}
				}
				
				$page_controls->setCurrentIndex($restore_current_index);
			}

			if ($include) $return_controls->add($page_control);
			
		}

		return $return_controls;		
	}
	
	public static function getControlsByTemplateId($template_id) {
		$show_drafts = false;
		$page_controls = new PageControlDAO();
		if (Page::isAdminRequest()) $show_drafts = true;
		return $page_controls->getControlsByTemplateId($template_id, $show_drafts);
	}
	
	public static function getPageControlById($page_control_id) {
		$page_control = new PageControlDAO();
		return $page_control->getPageControlById($page_control_id);
	}
	
	public static function getFavoritePageControls() {
		$page_control_dao = new PageControlDAO();
		return $page_control_dao->getFavoritePageControls();
	}
	
	/**
	 * Returns an array of fields that should be mirrored for controls that mirror other controls
	 */
	public static function _getMirroredFields() { return array('config', 'control_id', 'class_name', 'control_src'); }
	
	public static function buildControlByPageControlId(int $page_control_id, $edit_mode=null, $edit_context=null, $window_mode=null, $new_control_id=null) { // Can be database id "page_control->id" or object from PageControlLogic::getPageControlById();
		if (!is_numeric($page_control_id)) throw new \InvalidArgumentException('Expecting numeric value for page_control_id');
		$page_control = PageControlLogic::getPageControlById($page_control_id);

		return self::buildControlByPageControl($page_control, $edit_mode, $edit_context, $window_mode, $new_control_id);
	}

	public static function buildControlByPageControl(PageControlStruct $page_control_struct, $edit_mode=null, $edit_context=null, $window_mode=null, $new_control_id=null) { // Can be database id "page_control->id" or object from PageControlLogic::getPageControlById();
		/**
		 * Grabs mirrored control values and applies them to this control
		 */
		if (is_numeric($page_control_struct->mirror_id) && $page_control_struct->mirror_id > 0) {
			$mirror_replace = PageControlLogic::_getMirroredFields();
			
			$get_controls_vars = get_object_vars($page_control_struct);
			
			// Get control to mirror
			$new_controls = PageControlLogic::getPageControlById($page_control_struct->mirror_id);
			$new_controls_vars = get_object_vars($new_controls);

			foreach($mirror_replace as $val) {
				if (array_key_exists($val, $get_controls_vars) && array_key_exists($val, $new_controls_vars)) {
					$page_control_struct->$val = $new_controls->$val;
				}
			}
		}

		$control_file_path = PathManager::translate($page_control_struct->control_src);
		include_once($control_file_path);
		$class_name = $page_control_struct->class_name;

		$config_text = $page_control_struct->config;
		// Begin adding support for CWI_CONTROLS_EDITABLE_AbstractEditInPlaceControl, which uses serialized config values, instead of text 
		#$config_text_length = strlen($config_text);
		$second_char = substr($config_text, 1, 1);
		
		$init_array = array();
		$is_managed_control = false;
		$config = new ConfigDictionary();

		if ($second_char == ':' || $second_char == ';') { // Assume serialized config - using CWI_CONTROLS_EDITABLE_AbstractEditInPlaceControl
			$is_managed_control = true;
			
			#$config_array = unserialize($config_text);
			$config = ConfigDictionary::createFromString($config_text);
		} else { // EditableControl - old - phasing out

			// Convert old style config to new
			$init_array = Control::ParseConfigString($config_text);
			foreach($init_array as $config_name=>$config_value) { // Convert old param system to new system
				$config->set($config_name, $config_value);
			}
			$page_control_struct->config = $config->toString();

			// Saved upgraded config style
			self::save($page_control_struct);
		}

		$control = new $class_name();

		if ($control instanceof EditableControl || $control instanceof CWI_CONTROLS_EDITABLE_AbstractEditInPlaceControl || $control instanceof CWI_CONTROLS_AbstractPageControl) {
			$control->setConfig($config);
			$control->setPageControlId($page_control_struct->id);
			$control->setControlId($page_control_struct->control_id);
			$control->setPageId($page_control_struct->page_id);
			if (!empty($edit_mode)) $control->setEditMode($edit_mode);
			if (!empty($edit_context)) $control->setEditContext($edit_context);
			if (!empty($window_mode)) $control->setWindowMode($window_mode);
			if ($control instanceof PageControlControl) {
				$control->setLocalPath(dirname($control_file_path) . '/');
			}
		}

		if ($control instanceof PageControlControl) { // Legacy
			$control->setConfig($config);
			$control->setPageControlId($page_control_struct->id);
			$control->setControlId($page_control_struct->control_id);
			$control->setControlFriendlyName($page_control_struct->control_label);
			$control->setPageId($page_control_struct->page_id);
			$control->setId($page_control_struct->placeholder . '_' . $page_control_struct->id);
			$control->setPlaceholder($page_control_struct->placeholder);

			return $control;

		} else if ($control instanceof CWI_CONTROLS_IPageControl) {

			$id = $page_control_struct->placeholder . '_';
			$page_control_id = $page_control_struct->id;
			
			if (empty($page_control_id)) {
				if (!empty($new_control_id)) {
					#$id .= $new_sequence_id;
					$id = $new_control_id;
				} else {
					$id .= 'new';
				}
			}
			else $id .= $page_control_id;
			
			$control->setConfig($config);
			$control->setPageControlId($page_control_struct->id);
			$control->setControlId($page_control_struct->control_id);
			$control->setPageId($page_control_struct->page_id);
			$control->setPageTemplateId($page_control_struct->template_id);
			$control->setId($id);
			$control->setPlaceholder($page_control_struct->placeholder);
			$control->setSortorder($page_control_struct->sortorder);
			$control->setControlFriendlyName($page_control_struct->control_label);
			$control->isFavorite( ($page_control_struct->is_favorite==1) );
			$control->setFavoriteTitle($page_control_struct->favorite_title);

			return $control;
		} else {
			$page_control = new PageControlControl($page_control_struct);
			$page_control->m_childControl = $control;

			$page_control->setControlFriendlyName($page_control_struct->control_label);
			return $page_control;
		}

	}
	
	public static function buildNewControl($page_control_struct, $edit_mode=null, $edit_context=null, $window_mode=null, $new_control_id=null) {
		FrameworkManager::loadLogic('control');
		$control_struct = ControlLogic::getControlById($page_control_struct->control_id);
		
		$page_control_struct->class_name = $control_struct->class_name;
		$page_control_struct->control_src = $control_struct->file_src;
		$page_control_struct->control_label = $control_struct->label;
		
		return PageControlLogic::buildControlByPageControl($page_control_struct, $edit_mode, $edit_context, $window_mode, $new_control_id);
	}
	
	public static function save($page_control_struct) {
		$page_control = new PageControlDAO();

		return $page_control->save($page_control_struct);
	}
	
	public static function moveUp($page_control_id) {
		$page_control_dao = new PageControlDAO();
		$page_control = $page_control_dao->load($page_control_id);
		if ($page_control->sortorder > 1) { // Only move up if not the first position
			$move_from = $page_control->sortorder;
			$move_to = $page_control->sortorder - 1;
			
			$page_control->sortorder = $move_to;
			
			$swap_page_control = $page_control_dao->getPageControlBySortOrder($page_control->page_id, $page_control->placeholder, $move_to);
			$swap_page_control->sortorder = $move_from;
			
			$page_control_dao->save($page_control);
			$page_control_dao->save($swap_page_control);
			
		}
	}
	
	public static function moveDown($page_control_id) {
		$page_control_dao = new PageControlDAO();
		$page_control = $page_control_dao->load($page_control_id);
		$max_sortorder = PageControlLogic::getNextSortOrder($page_control->page_id, $page_control->placeholder) - 1;

		if ($page_control->sortorder < $max_sortorder) {
			$move_from = $page_control->sortorder;
			$move_to = $page_control->sortorder + 1;
			$page_control->sortorder = $move_to;
			
			$swap_page_control = $page_control_dao->getPageControlBySortOrder($page_control->page_id, $page_control->placeholder, $move_to);
			$swap_page_control->sortorder = $move_from;

			$page_control_dao->save($page_control);
			$page_control_dao->save($swap_page_control);
		}
	}
	
	public static function delete($page_control_id) { // Integer primary key
		$page_control = new PageControlDAO();
		$page_control->delete($page_control_id);
	}
	
	public static function createOrUpdatePageControl($page_control_id, $page_id, $template_id, $placeholder, $sortorder, $control_id, $config) {
		FrameworkManager::loadStruct('pagecontrol');
		if (empty($page_id)) $page_id = 0;
		if (empty($template_id)) $template_id = 0;
		
		if (!is_a($config, 'ConfigDictionary')) throw new Exception('Invalid configuration type: ' . get_class($config));
		
		$page_control = new PageControlStruct();
		
		if (!empty($page_control_id)) {
			$page_control->id		= $page_control_id;
		}
		$page_control->control_id	= $control_id;
		$page_control->page_id		= $page_id;
		$page_control->template_id	= $template_id;
		$page_control->placeholder	= $placeholder;
		
		if (empty($sortorder)) {
			$page_control->sortorder	= PageControlLogic::getNextSortOrder($page_control->page_id, $page_control->placeholder);
		} else {
			$page_control->sortorder = $sortorder;
		}
		
		$page_control->config = $config->toString();
		$page_control = PageControlLogic::save($page_control);
		return $page_control;
	}
}

?>