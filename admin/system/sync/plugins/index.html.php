<?php

FrameworkManager::loadManager('sync');

$page_title = 'Sync Plugins';
if ($site_name = ConfigurationManager::get('SITE_NAME')) $page_title .= ' for ' . $site_name;
$page_title .= ' (' . ConfigurationManager::get('DOMAIN') . ')';

$lbl_plugin_info = Page::getControlById('lbl_plugin_info');
$lbl_plugin_info->setText($page_title);

Page::setTitle($page_title);

if (Page::get('nocache')) $cache_timeout = 1;
else $cache_timeout = 24 * 60 * 60; // 24 hours

$valid = true;
try {
	$rs_plugins = CWI_MANAGER_SyncManager::getAvailablePlugins($cache_timeout);
} catch (Exception $e) {
	$valid = false;
	ErrorManager::addError($e->getMessage());
}

$plugin_path = ConfigurationManager::get('DIR_FS_PLUGINS');

if (!is_writable( $plugin_path )) {
	if (!@mkdir($plugin_path, 0777, true)) ErrorManager::addError('Plugin path is not writable: ' . $plugin_path);
}

if ($valid) {
	while ($plugin = $rs_plugins->getNext()) {
		$plugin->link = urlencode($plugin->link);
		
		$plugin->style = '';
		if (ErrorManager::anyDisplayErrors()) {
			$plugin->status = '<span style="color:red;font-style:italic;">See error</span>';
		} else {
			if ($plugin->system_installed == 1) {
				$plugin->style = 'color:green;font-weight:bold;';
				$plugin->status = '<span style="' . $plugin->style . '">Installed</span>';
			} else {
				$plugin->style = 'color:red;font-weight:bold;';
				$plugin->status = '<a href="install.html?plugin=' . $plugin->name . '&retrievallink=' . $plugin->link . '" style="' . $plugin->style . '">Install Now</a>';
			}
		}
		$plugin->display_name = '<span style="' . $plugin->style . '">' . $plugin->name . '</span>';
		
		$rs_plugins->setAt($rs_plugins->getCurrentIndex(), $plugin);
	}
	Page::getControlById('dg_plugins')->setData($rs_plugins);
}

?>