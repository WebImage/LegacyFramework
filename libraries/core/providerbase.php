<?php

class ProviderBase {
	private $name; // String
	private $config; // ProviderDictionary
	
	/**
	 * Not needed for implementation:
	 *
	 *	var $m_name; // Name this provider will be referenced by
	 *	var $m_classFile; // Physical location of class file
	 *	var $m_className; // Class name
	 *	var $m_applicationName; // The application this provider is associated with
	 *
	 *	function ProviderBase($name, $class_file, $class_name, $application_name) {
	 *		$this->m_name = $name;
	 *		$this->m_classFile = PathManager::translate($class_file);
	 *		$this->m_className = $class_name;
	 *		$this->m_applicationName = $application_name;
	 *	}
	 */
	function init($name, $config=null) {
		$this->name = $name;
		if (is_null($config) || (is_object($config) && is_a($config, 'ProviderDictionary'))) $config = new ProviderDictionary();
		$this->config = $config;
		return true;
	}
	function getName() { return $this->name; }
	public function getConfigValue($name) { return $this->config->get($name); }
	public function setConfigValue($name, $value) { $this->config->set($name, $value); }
}