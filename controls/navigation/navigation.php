<?php

FrameworkManager::loadManager('navigation');

class NavigationControl extends WebControl {
	//NavigationManager::addNavItem('main', ConfigurationManager::get('DIR_WS_HOME'), 'Home');
	var $m_navId; // Nav id to use for NavigationManager
	var $m_headerTemplate;
	
	var $m_itemHeaderTemplate = '';
	var $m_itemTemplate = '<a href="<Data field="link" />" title="<Data field="name_id" />"><Data field="name" /></a>';
	var $m_itemFooterTemplate = '';
	var $m_footerTemplate;
	var $m_orientation = 'horizontal'; // horizontal | vertical
	var $m_layoutType; // Flat | Tree | Dropdown
	var $m_includeRoot = false;
	var $m_levelsToInclude = 1;
	
	function __construct($init_array=array()) {
		parent::__construct($init_array);
	}
	
	function getNavId() { if (empty($this->m_navId)) return false; else return $this->m_navId; }
	function setNavId($nav_id) { $this->m_navId = $nav_id; }
	
	function prepareContent() {
		if ($this->getNavId()) {
			if ($navigation = NavigationManager::getNavigation($this->getNavId())) {
				foreach($navigation as $nav) {
					$output = '';
					$output .= $this->m_itemHeaderTemplate;
					$output .= $this->m_itemTemplate;
					$output .= $this->m_itemFooterTemplate;
					
					// Replacements
					$output = str_replace('<Data field="link" />', $nav->getLink(), $output);
					$output = str_replace('<Data field="name" />', $nav->getName(), $output);
					$output = str_replace('<Data field="name_id" />', $nav->getLinkId(), $output);
				}
			}
		} else {
			$this->setRenderedContent('NAVIGATION');
		}
	}
}

?>