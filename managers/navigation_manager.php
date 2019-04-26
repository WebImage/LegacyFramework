<?php

class NavigationItem {
	var $_link;
	var $_name;
	var $_linkId;
	function __construct($link, $name, $link_id) {
		$this->_link = $link;
		$this->_name = $name;
		$this->_linkId = $link_id;
	}
	function getLink() { return $this->_link; }
	function getName() { return $this->_name; }
	function getLinkId() { return $this->_linkId; }
}

define('NAVIGATION_ORIENTATION_HORIZONTAL', 1);
define('NAVIGATION_ORIENTATION_VERTICAL', 1);

class NavigationCollection extends Collection{
	var $id;
	
	var $_orientation = null; // Horizontal | Vertical
	var $_allowMultipleSelections;
	
	var $_templateItem;
	var $_templateSelectedItem;
	var $_templateDisabledItem;
	
	function __construct($id) {
		
	}
	
	function setOrientation($orientation) {
		$this->_orientation = 0;
	}
}

class NavigationManager {
	var $navigation = array(); // Navigation ID storage
	
	function addNavigationItem($nav_id, $link, $name, $link_id) {
		$instance = Singleton::getInstance('NavigationManager');
		if (!isset($instance->navigation[$nav_id])) $instance->navigation[$nav_id] = array();
		array_push($instance->navigation[$nav_id], new NavigationItem($link, $name, $link_id));
	}
	
	function getNavigation($nav_id) {
		$instance = Singleton::getInstance('NavigationManager');
		if (isset($instance->navigation[$nav_id])) {
			return $instance->navigation[$nav_id];
		} else {
			return false;
		}
	}
}

?>
