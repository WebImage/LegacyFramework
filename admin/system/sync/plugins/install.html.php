<?php

FrameworkManager::loadManager('sync');

$install	= Page::get('install', 'no');

$plugin = Page::get('plugin');
$retrieval_link = Page::get('retrievallink');
if (Page::get('nocache')) {
	$cache_timeout = 1;
} else {
	$cache_timeout = 24 * 60 * 60; // 24 hours
}

$valid = true;
try {

	$plugin = CWI_MANAGER_SyncManager::getPluginByLink($plugin, $retrieval_link, $cache_timeout);
	Page::setTitle('Check Plugin Requirements: ' . $plugin->getName());
echo '<pre>';
print_r($plugin);exit;
	$plugin_tree = CWI_MANAGER_SyncManager::buildPluginInstallationTree($plugin, $cache_timeout);
	
	if ($install == 'yes') {
		try {
			$install_status = $plugin_tree->install();
			if ($install_status < 0) ErrorManager::addError('Install tree problem: ' . $plugin_tree->getInstallationStatus());
		} catch (Exception $e) {
			ErrorManager::addError($e->getMessage());
		}
		if (!ErrorManager::anyDisplayErrors()) {
			NotificationManager::addMessage('Plugin successfully installed.');
		}
	}
	
	$rs_installation_manifest = CWI_MANAGER_SyncManager::buildInstallationManifestResultSet($plugin_tree);

	
	while ($struct = $rs_installation_manifest->getNext()) {
		
		$space = '';
		for ($i=0; $i < $struct->level; $i++) {
			$space .= '&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;';
		}
		$style='';
		/*
		switch ($struct->description) {
			case 'Model':
				$style = 'color:#dd9933;';
				break;
			case 'Plugin':
				$style = 'color:#cc0099;text-transform:uppercase';
				break;
			case 'Resource File':
				$style = 'color:#996633;font-style:italic;';
				break;
			default:
				$style = '';
		}
		*/
		if ($struct->installed == 1) {
			$struct->status = 'Installed';
		} else {
			$struct->status = 'Not Installed';
			$style = 'color:red;';
		}
		
		$struct->display_name = $space . '<span style="' . $style . '">' . $struct->description . ' - <strong>' . $struct->name . '</strong></span>';
		$struct->status = '<span style="' . $style . ' ">' . $struct->status . '</span>';
		$rs_installation_manifest->setAt($rs_installation_manifest->getCurrentIndex(), $struct);
	}
	
	$dg_requirements = Page::getControlById('dg_requirements');
	$dg_requirements->setData($rs_installation_manifest);
	
} catch (Exception $e) {
	$valid = false;
	ErrorManager::addError($e->getMessage());
}

?>