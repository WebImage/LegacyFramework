<?php

FrameworkManager::loadLibrary('plugins.plugincollection');

class CWI_PLUGIN_PluginInstallException extends Exception {}

abstract class CWI_PLUGIN_Plugin {
	private $name, $version;
	/**
	 * @var CWI_PLUGIN_PluginCollection collection of plugins
	 */
	private $plugins;
	/** 
	 * @var CWI_PLUGIN_PluginRequirementCollection collection of files waiting to be downloaded as plugins
	 */
	private $requirements;
	/**
	 * @var bool Whether or not the installation check should be performed.  Would primarily only be set to true if another process is already sure that this plugin will be installed as another part of a different process
	 */
	private $skipInstall = false;
	/**
	 * @var bool Whether or not the plugin has already been installed;
	 */
	private $installed = false;
	/**
	 * @var string Error message
	 */
	private $installationStatus;
	/**
	 * @param string $name The system name for the plugin
	 * @param int $version The version of the plugin
	 */
	public function __construct($name, $version=1) {
		$this->name = $name;
		$this->version = $version;
		$this->plugins = new CWI_PLUGIN_PluginCollection();
		$this->requirements = new CWI_PLUGIN_PluginRequirementCollection();
	}
	// Getters
	public function getName() { return $this->name; }
	public function getVersion() { return $this->version; }
	public function getPlugins() { return $this->plugins; }
	public function getPluginCount() { return $this->plugins->getCount(); }
	public function getRequirements() { return $this->requirements; }
	
	// Getters/Setters
	public function skipInstall($true_false=null) {
		if (is_null($true_false)) return $this->skipInstall;
		else $this->skipInstall = $true_false;
	}
	public function isInstalled($true_false=null) {
		if(is_null($true_false)) return $this->installed;
		else $this->installed = $true_false;
	}
	public function getInstallationStatus() { return $this->installationStatus; }
	
	// Setters
	public function addRequirement($requirement) { $this->requirements->add($requirement); }
	public function addPlugin($plugin) { $this->plugins->add($plugin); }
	protected function setInstallationStatus($message) { $this->installationStatus = $message; }
	
	// Methods
	/**
	 * Install this plugin and all children plugins.  Children plugins will be installed first to ensure that the 
	 */
	private function installChildren() {
		$plugins = $this->getPlugins();
		if ($plugins->getCount() == 0) {
			$this->setInstallationStatus('No children to install.');
			return 0;
		}
		$status = 0; // Indicate installation success, but that nothing was installed (everything was already installed)
		while ($plugin = $plugins->getNext()) {
			$plugin_install_status = $plugin->install();
			if ($plugin_install_status > 0) {
				$status = 1; // If anything new has been installed, reflect that in the returned status
			} else if ($plugin_install_status < 0) {
				$this->setInstallationStatus($plugin->getInstallationStatus());
				return -1;
			}
		}
		return $status;
	}
	/**
	 * @return int 0=Nothing to Install/Already Installed, 1=Installed, <0 error
	 */
	protected function executeInstallation() { return -1; }
	
	public function install($auto_install_children=true) {
		// Install siblings first to make sure requirements are install
		$children_status = 0;
		if ($auto_install_children) $children_status = $this->installChildren();
		
		if ($children_status >= 0) {
		/**
		 * Commented out isInstalled() below to just assume that if this is being called it should be installed or re-installed
		 **/
		#	if ($this->isInstalled()) {
		#		$this->setInstallationStatus('Already installed.');
		#		$status = 0;
		#	} else {
				$status = $this->executeInstallation();
		#	}
		} else {
			$status = -1;
		}
		return $status;
	}
	
}

?>