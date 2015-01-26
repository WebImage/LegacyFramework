<?php

FrameworkManager::loadLibrary('plugins.plugin.standard');

class CWI_SYNC_PluginConversionException extends Exception {}
class CWI_SYNC_PluginHelper {
	function convertXmlToPlugin($xml_plugin) {
		FrameworkManager::loadLibrary('plugins.pluginrequirement.plugin');
		FrameworkManager::loadLibrary('plugins.pluginrequirement.model');
		FrameworkManager::loadLibrary('plugins.pluginrequirement.resourcefile');

		if (!is_a($xml_plugin, 'CWI_XML_Traversal')) throw new CWI_SYNC_PluginConversionException('Invalid XML');
		
		$path = ConfigurationManager::get('DIR_FS_PLUGINS') . $xml_plugin->getParam('name') . '/';
		$plugin = new CWI_PLUGIN_StandardPlugin( $xml_plugin->getParam('name'), $path, $xml_plugin->getParam('friendlyName'), $xml_plugin->getParam('version') );
		return $plugin;
	}
	
	function convertXmlRequirementsToPluginRequirementCollection($xml_requirements) { /* CWI_XML_Traversal <requirements><models /><plugins /><resourceFiles /></requirements> */
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
	
	function convertModelToPlugin($model) { // Model
		return new CWI_PLUGIN_ModelPlugin($model->getName(), $model, $model->getVersion());
	}
}

?>