<?php

use WebImage\Core\LegacyDictionary;

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
	 *	function __construct($name, $class_file, $class_name, $application_name) {
	 *		$this->m_name = $name;
	 *		$this->m_classFile = PathManager::translate($class_file);
	 *		$this->m_className = $class_name;
	 *		$this->m_applicationName = $application_name;
	 *	}
	 */
	public function init($name, $config=null) {
		$this->name = $name;
		if (null === $config) $config = new LegacyDictionary();
		$this->config = $config;
		return true;
	}
	public function getName() { return $this->name; }
	public function setName($name) { $this->name = $name; }
	public function getConfigValue($name) { return $this->config->get($name); }
	public function setConfigValue($name, $value) { $this->config->set($name, $value); }
}
