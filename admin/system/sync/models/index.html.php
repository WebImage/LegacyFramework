<?php

FrameworkManager::loadManager('sync');

$page_title = 'Sync Database Models';
if ($site_name = ConfigurationManager::get('SITE_NAME')) $page_title .= ' for ' . $site_name;
$page_title .= ' (' . ConfigurationManager::get('DOMAIN') . ')';

$lbl_sync_info = Page::getControlById('lbl_sync_info');
$lbl_sync_info->setText($page_title);

Page::setTitle($page_title);

$installed_status = Page::get('installedstatus', SessionManager::get('installedstatus', 'any'));
$config_status = Page::get('configstatus', SessionManager::get('configstatus', 'any'));

SessionManager::set('installedstatus', $installed_status);
SessionManager::set('configstatus', $config_status);
/**
 * Update page values in case they were modified by SessionManager::get()
 */
Page::set('installedstatus', $installed_status);
Page::set('configstatus', $config_status);

if (Page::get('nocache')) $cache_timeout = 1;
else $cache_timeout = 24 * 60 * 60; // 24 hours

$valid = true;
try {
	$models = CWI_MANAGER_SyncManager::getAvailableModels($cache_timeout);
} catch (Exception $e) {
	$valid = false;
	ErrorManager::addError($e->getMessage());
}

$rs_models = new ResultSet();
if ($valid) {
	$dao = new DataAccessObject();
	
	$table_stack = array();
	$model_stack = array();
	if ($models->getCount() > 0) { // No sense in processing this unless there are actual models available
		$tables = $dao->getDatabaseTables();
		while ($table = $tables->getNext()) {
			array_push($table_stack, $table->name);
		}
	}

	while ($model = $models->getNext()) {
		$model->link = urlencode($model->link);

		array_push($model_stack, $model->name);
		
		$table_physically_exists = (in_array($model->table, $table_stack) );
		$show_row = false;
		
		if ($model->table_defined) { // check if this is defined in the config
			if ($table_physically_exists) {
				if ( ($installed_status == 'any' || $installed_status == 'yes') && ($config_status == 'any' || $config_status == 'yes') ) $show_row = true;
				$model->style = 'color:green;font-style;italic;font-weight:bold;';
				$model->status = '<a href="installmodel.html?model=' . $model->name . '"><span style="' . $model->style . '">Installed</span></a>';
			} else {
				if ( ($installed_status == 'any' || $installed_status == 'no') && ($config_status == 'any' || $config_status == 'yes') ) $show_row = true;
				$model->style = 'color:#3366cc;font-style:italic;';
				$model->status = '<span style="' . $model->style . '">Configured Only</span>';
				$model->status = '<a href="installmodel.html?model=' . $model->name . '&retrievallink=' . $model->link . '" style="' . $model->style . '">Install Now</a> (Configured)';
			}
		} else {
			if ($table_physically_exists) { //dao->tableExists($model->table)) {
				if ( ($installed_status == 'any' || $installed_status == 'yes') && ($config_status == 'any' || $config_status == 'no') ) $show_row = true;
				$model->style = 'color:purple;font-weight:bold;';
				$model->status = '<a href="installmodel.html?model=' . $model->name . '"><span style="' . $model->style . '">Installed.  Not Configured</span></a>';
			} else {
				$model->style = 'color:red;font-weight:bold;';
				if ( ($installed_status == 'any' || $installed_status == 'no') && ($config_status == 'any' || $config_status == 'no') ) $show_row = true;
				$model->status = '<a href="installmodel.html?model=' . $model->name . '&retrievallink=' . $model->link . '" style="' . $model->style . '">Install Now</a> (No Config)';
			}
		}
		
		$model->config_value = '';
		if (!$model->table_defined) {
			$model->config_value = htmlentities('<add name="' . $model->name . '" value="' . $model->table . '" />');
		}
		
		$model->name_w_style = '<span style="' . $model->style . '">' . $model->name . '</span>';
		$models->setAt($models->getCurrentIndex(), $model);
			
		if ($show_row) $rs_models->add($model);
	}
	$dg_models = Page::getControlById('dg_models');
	$dg_models->setData($rs_models);
}

$rs_installed_status = ResultSetHelper::buildResultSetFromArray(array(
	'any'	=> 'Any',
	'yes'	=> 'Yes',
	'no'	=> 'No'
	));
$rs_config_status = ResultSetHelper::buildResultSetFromArray(array(
	'any'	=> 'Any',
	'yes'	=> 'Yes',
	'no'	=> 'No'
	));
$txt_installed_status = Page::getControlById('installedstatus');
$txt_installed_status->setData($rs_installed_status);
$txt_config_status = Page::getControlById('configstatus');
$txt_config_status->setData($rs_config_status);

?>