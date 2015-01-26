<?php

FrameworkManager::loadManager('sync');
#FrameworkManager::loadLibrary('sync.databasehelper');
FrameworkManager::loadLibrary('db.tablecreator');

$retrieve_model	= Page::get('model');
$retrieval_link	= Page::get('retrievallink');
$install	= Page::get('install', 'no');
#$table_name	= Page::get('tablename');
if (empty($retrieve_model)) Page::redirect('index.html');

Page::setTitle('Install Model: ' . $retrieve_model);

if (Page::get('nocache')) {
	$cache_timeout = 1;
} else {
	$cache_timeout = 24 * 60 * 60; // 24 hours
}

try {
	if (!$model_plugin = CWI_MANAGER_SyncManager::getModelPluginByLink($retrieve_model, $retrieval_link, $cache_timeout)) {
		ErrorManager::addError('Sync manager was unable to load an xml model for: ' . $retrieve_model);
	}
} catch (CWI_MANAGER_SyncException $e) {
	ErrorManager::addError('There was a problem with the request: ' . $e->getMessage());
}

if (!ErrorManager::anyDisplayErrors()) {
	
	$plugin_tree = CWI_MANAGER_SyncManager::buildPluginInstallationTree($model_plugin);
	
	$time2 = FrameworkManager::getTime();
	
	if ($install == 'yes') {
		try {
			$install_status = $plugin_tree->install();
			if ($install_status < 0) ErrorManager::addError('Install tree problem: ' . $plugin_tree->getInstallationStatus());
		} catch (Exception $e) {
			ErrorManager::addError($e->getMessage());
		}
		if (!ErrorManager::anyDisplayErrors()) {
			NotificationManager::addMessage('Model successfully installed.');
		}
	}
	
	$rs_installation_manifest = CWI_MANAGER_SyncManager::buildInstallationManifestResultSet($plugin_tree);
	
	while ($requirement = $rs_installation_manifest->getNext()) {
		$requirement->status = ($requirement->installed == 1) ? 'Installed':'Will be installed';
		$rs_installation_manifest->setAt($rs_installation_manifest->getCurrentIndex(), $requirement);
	}
	$dg_requirements = Page::getControlById('dg_requirements');
	$dg_requirements->setData($rs_installation_manifest);

	$model = $plugin_tree->getModel();
	
	$lbl_model = Page::getControlById('lbl_model');
	$lbl_model->setText($model->getName());
	
	$lbl_status = Page::getControlById('lbl_status');
	
	$table_creator = CWI_DB_TableCreatorFactory::createFromModel($model);
	
	if ($table_creator->tableExists( $model->getTableName() )) {
		$lbl_status->setText('Installed');
	} else {
		$lbl_status->setText('Not Installed.  <a href="installmodel.html?model=' . $retrieve_model . '&retrievallink=' . urlencode($retrieval_link) . '&install=yes">Install Now</a>');
	}
	
	$lbl_table_name = Page::getControlById('lbl_table_name');
	$lbl_table_name->setText($model->getTableName());
	
	$rs_fields = new ResultSet();
	
	$model_fields = $model->getFields();
	foreach($model_fields as $model_field) {
		$field_struct 			= new stdClass();
		$field_struct->name		= $model_field->getName();
		$field_struct->type		= strtoupper($model_field->getType());
		$field_struct->required		= ($model_field->isRequired()) ? 'X':'&nbsp;';
		$field_struct->size		= $model_field->getSize();
		$field_struct->scale		= $model_field->getScale();
		$field_struct->default		= $model_field->getDefault();
		$field_struct->primary_key	= ($model_field->isPrimaryKey()) ? 'X':'&nbsp;';
		$field_struct->auto_increment	= ($model_field->isAutoIncrement()) ? 'X':'&nbsp;';
		$rs_fields->add($field_struct);
	}
	$dg_fields = Page::getControlById('dg_fields');
	$dg_fields->setData($rs_fields);
	
}


?>