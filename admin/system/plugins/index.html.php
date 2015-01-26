<?php
FrameworkManager::loadLibrary('xml.compile');
FrameworkManager::loadLogic('plugin');

if ($install_plugin = Page::get('install')) {
	
	$plugin_path = ConfigurationManager::get('DIR_FS_PLUGINS') . $install_plugin . '/';
		
	FrameworkManager::loadLibrary('plugins.pluginfactory');
	
	// Build a plugin object from the local
	$plugin = CWI_PLUGIN_PluginFactory::createInstallablePluginFromLocalDir($plugin_path);
		
	$result = $plugin->install();
	
	if ($result < 0) ErrorManager::addError($plugin->getInstallationStatus());	
	else {
		FrameworkManager::loadStruct('plugin');
		$plugin_struct = new PluginStruct();
		$plugin_struct->enable = 1;
		$plugin_struct->friendly_name = $plugin->getFriendlyName();
		$plugin_struct->name = $plugin->getName();
		$plugin_struct->path = $plugin_path;
		$plugin_struct->version = $plugin->getVersion();
		
		PluginLogic::save($plugin_struct);
		
		NotificationManager::addMessage('The plugin ' . $plugin->getFriendlyName() . ' was installed');
		
	}
	
}

$rs_installed_plugins = PluginLogic::getInstalledPlugins();
$dg_installed_plugins = Page::getControlById('dg_installed_plugins');
$dg_installed_plugins->setData($rs_installed_plugins);

$rs_notinstalled_plugins = PluginLogic::getNotInstalledPlugins();
$dg_notinstalled_plugins = Page::getControlById('dg_notinstalled_plugins');
$dg_notinstalled_plugins->setData($rs_notinstalled_plugins);

?>