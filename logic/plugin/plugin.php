<?php
/**
 * 01/27/2010	(Robert Jones) Modified class to take advantage of the fact that CWI_XML_Compile::compile() now throws errors
 */
class PluginLogic {
	
	/**
	 *
	 */
	public static function getPlugins() {
		FrameworkManager::loadDAO('plugin');
		$dao_plugin = new PluginDAO();
		return $dao_plugin->getPlugins();
	}
	public static function getInstalledPlugins() {
		// Load Plugin Requisites
		FrameworkManager::loadDAO('plugin');
		
		
		// Instantiate Plugin Data Access Object
		$dao_plugin = new PluginDAO();
		// Retrieve plugins
		return $dao_plugin->getAllPlugins();
	}
	public static function getInstalledPluginByName($name) {
		// Load Plugin Requisites
		FrameworkManager::loadDAO('plugin');
		
		// Instantiate Plugin Data Access Object
		$dao_plugin = new PluginDAO();
		// Retrieve plugins
		return $dao_plugin->getPluginByName($name);
	}
	public static function getNotInstalledPlugins() {
		FrameworkManager::loadDAO('plugin');
		FrameworkManager::loadStruct('plugin');
		
		// Instantiate Plugin Data Access Object
		$dao_plugin = new PluginDAO();
		// Retrieve plugins
		$installed_plugins = $dao_plugin->getAllPlugins();
		$exclude_plugin_list = array();
		while ($installed_plugin = $installed_plugins->getNext()) array_push($exclude_plugin_list, $installed_plugin->name);
		
		$rs_plugins = new ResultSet();
		#$plugin_directories = PathManager::getAllDirFiles('~/plugins/');
		$plugin_directories = PathManager::getAllDirFiles(ConfigurationManager::get('DIR_FS_PLUGINS'));

		foreach($plugin_directories as $directory) {
			$config_path = $directory . 'config/plugin.xml';
			if (file_exists($config_path)) {
				$valid_xml = true;
				try {
					$xml = CWI_XML_Compile::compile( file_get_contents($config_path) );
				} catch (CWI_XML_CompileException $e) {
					$valid_xml = false;
				}
				
				if ($valid_xml) {
					if ($xml_plugin = $xml->getPathSingle('/plugin')) {
						$plugin_name = $xml_plugin->getParam('name');
						$plugin_friendly = $xml_plugin->getParam('friendlyName');
						$plugin_version = $xml_plugin->getParam('version');
						if (empty($plugin_version)) $plugin_version = 1;
						if (!in_array($plugin_name, $exclude_plugin_list)) {
							#echo '-- has config: ' . $plugin_name . ' - ' . $plugin_friendly .'<br />';
							$plugin_struct = new PluginStruct();
							$plugin_struct->name		= $plugin_name;
							$plugin_struct->friendly_name	= $plugin_friendly;
							$plugin_struct->version		= $plugin_version;
							$rs_plugins->add($plugin_struct);
						}
					}
				}
			}
		}
		return $rs_plugins;
	}
	public static function save($plugin_struct) {
		FrameworkManager::loadDAO('plugin');
		$dao_plugin = new PluginDAO();
		if (!PluginLogic::getInstalledPluginByName($plugin_struct->name)) $dao_plugin->setForceInsert(true); // Does not already exist, create it
		return $dao_plugin->save($plugin_struct);
	}
	
}

?>