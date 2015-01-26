<?php

interface CWI_CONTROLS_IPageControl {
	public function getLocalPath(); // Gets string path to local file directory
	public function getPageControlId();
	public function getPageControlTitle();
	public function getPageId();
	public function getControlId();
	public function getControlFriendlyName();
	public function getPlaceholder();
	public function getSortorder();

	public function getConfig(); // ConfigDictionary
	public function getConfigValue($name);
	public function getEditMode();
	public function getWindowMode();
	
	/**
	 * @return bool Whether this control is marked as a favorite piece of content or not
	 **/
	public function isFavorite($true_false=null);
	public function getFavoriteTitle();
	
	public function setLocalPath($path); // String path to local file directory
	public function setPageControlId($page_control_id);
	public function setPageControlTitle($title);
	public function setPageId($page_id);
	public function setControlId($control_id);
	public function setControlFriendlyName($friendly_name);
	public function setPlaceholder($placeholder);
	public function setSortorder($sortorder);
	
	public function setConfig(ConfigDictionary $config);
	public function setConfigValue($name, $value);
	public function setEditMode($edit_mode);
	public function setWindowMode($window_mode);
	
	public function setFavoriteTitle($title);	
}