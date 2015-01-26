<?php

FrameworkManager::loadLibrary('plugins.pluginrequirementcollection');
interface CWI_PLUGIN_IPluginRequirement {
	public function getName(); // string
	public function getLink(); // string
	public function getRequiredVersion(); // int
	public function getDescription(); // string
	public function isInstalled($true_false=null); // NULL or Bool
	public function getPluginIndex(); // int
	#public function setPluginIndex(); // int
}
class CWI_PLUGIN_PluginRequirement implements CWI_PLUGIN_IPluginRequirement {
	private $name, $link, $requiredVersion=false, $installed, $pluginIndex;
	protected $description = 'Generic';
	public function __construct($name, $link, $required_version=1) {
		$this->name = $name;
		$this->link = $link;
		if (empty($required_version)) $required_version = 1;
		$this->requiredVersion = $required_version;
	}
	// Getters
	public function getName() { return $this->name; }
	public function getLink() { return $this->link; }
	public function getRequiredVersion() { return $this->requiredVersion; }
	public function getDescription() { return $this->description; }
	public function getPluginIndex() { return $this->pluginIndex; }
	
	/**
	 * Getter/Setter
	 */
	public function isInstalled($true_false=null) {
		if (is_null($true_false)) return $this->installed; // Getter
		else $this->installed = $true_false; // Setter
	}
	public function addRequirement($plugin_requirement) {
		$this->requirements->add($plugin_requirement);
	}
	
	// Setters
	/**
	 * Used if there is a plugin index associated with this plugin requirement (primarily for CWI_MANAGER_SyncManager)
	 */
	#public function setPluginIndex($index) { $this->pluginIndex = $index; }
}

?>