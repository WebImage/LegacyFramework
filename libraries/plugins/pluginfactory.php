<?php

FrameworkManager::loadLibrary('plugins.plugin.standard');

class CWI_PLUGIN_PluginConversionException extends Exception {}
class CWI_PLUGIN_PluginFactory {
	
	/**
	 * Create a plugin object from a local plugin directory
	 * 
	 * @todo Make it possible to create custom plugin installers, rather than just relying on the CWI_PLUGIN_StandardPlugin
	 * @access public static
	 * @return CWI_PLUGIN_StandardPlugin
	 **/
	public static function createInstallablePluginFromLocalDir($plugin_path) {
		FrameworkManager::loadManager('sync');
		
		$config_path = $plugin_path . 'config/plugin.xml';
		
		if (file_exists($config_path)) {
		} else {
			throw new Exception('Configuration file: ./config/plugin.xml is missing');
		}
		
		try {
			$xml_plugin = CWI_XML_Compile::compile( file_get_contents($config_path) );
		} catch (CWI_XML_CompileException $e) {
			throw new Exception('Configuration parse error: ' . $e->getMessage());
		}
		
		$plugin = self::convertXmlToPlugin($xml_plugin, $plugin_path);
		if ($xml_requirements = $xml_plugin->getPathSingle('requirements')) {
			$requirement_collection = self::convertXmlRequirementsToPluginRequirementCollection($xml_requirements);
			while ($requirement = $requirement_collection->getNext()) {
				$plugin->addRequirement($requirement);
			}
		}
		
		// Makes the plugin installable by adding in any external dependencies
		CWI_MANAGER_SyncManager::buildPluginInstallationTree($plugin);
		
		return $plugin;
	}
	
	public static function convertXmlToPlugin($xml_plugin, $path=null) {
		FrameworkManager::loadLibrary('plugins.pluginrequirement.plugin');
		FrameworkManager::loadLibrary('plugins.pluginrequirement.model');
		FrameworkManager::loadLibrary('plugins.pluginrequirement.resourcefile');

		if (!is_a($xml_plugin, 'CWI_XML_Traversal')) throw new CWI_SYNC_PluginConversionException('Invalid XML');
		
		if (empty($path)) {
			$path = ConfigurationManager::get('DIR_FS_PLUGINS') . $xml_plugin->getParam('name') . '/';
		}
		
		$plugin = new CWI_PLUGIN_StandardPlugin( $xml_plugin->getParam('name'), $path, $xml_plugin->getParam('friendlyName'), $xml_plugin->getParam('version') );
		$plugin->setXmlConfig($xml_plugin);
		
		return $plugin;
	}
	
	private static function convertXmlRequirementsToPluginRequirementCollection($xml_requirements) { /* CWI_XML_Traversal <requirements><models /><plugins /><resourceFiles /></requirements> */
		if (is_object($xml_requirements) && is_a($xml_requirements, 'CWI_XML_Traversal') && $xml_requirements->getTagName() == 'requirements') {
			$plugin_requirements = new CWI_PLUGIN_PluginRequirementCollection();
			if ($required_plugins = $xml_requirements->getPath('plugins/plugin')) {
				foreach($required_plugins as $required_plugin) {
					$plugin_requirement = new CWI_PLUGIN_PluginPluginRequirement($required_plugin->getParam('name'), $required_plugin->getParam('link'), $required_plugin->getParam('version'));
					$plugin_requirements->add( $plugin_requirement );
				}
			}
			if ($required_models = $xml_requirements->getPath('models/model')) {
				foreach($required_models as $required_model) {
					$plugin_requirement = new CWI_PLUGIN_ModelPluginRequirement($required_model->getParam('name'), $required_model->getParam('link'), $required_model->getParam('version'));
					$plugin_requirements->add( $plugin_requirement );
				}
			}
			if ($required_resource_files = $xml_requirements->getPath('resourceFiles/resourceFile')) {
				foreach($required_resource_files as $required_resource_file) {
					$plugin_requirement = new CWI_PLUGIN_ResourceFilePluginRequirement($required_resource_file->getParam('localPath'), $required_resource_file->getParam('link'), $required_resource_file->getParam('version'));
					$plugin_requirements->add( $plugin_requirement );
				}
			}
			return $plugin_requirements;
		} else throw new CWI_SYNC_PluginConversionException('Invalid requirements XML passed to convertXmlRequirementsToPluginRequirementCollection().');
	}
	
}

?>