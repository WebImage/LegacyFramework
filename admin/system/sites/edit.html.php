<?php

FrameworkManager::loadManager('sync');
FrameworkManager::loadLibrary('db.tablecreator');
FrameworkManager::loadLogic('site');

$crm_enabled = FrameworkManager::loadLogic('crm');

$site_struct = Page::getStruct('site');
$dir_fs_sites = dirname(ConfigurationManager::get('DIR_FS_FRAMEWORK_APP')).'/';

$rs_environment = ResultSetHelper::buildResultSetFromArray(array(
	'' => '-- Select Environment --',
	'development'=>'Development',
	'staging'=>'Staging',
	'production'=>'Production'
	));
	
if (Page::isPostBack()) {
	if (empty($site_struct->name)) ErrorManager::addError('Name is required.');
	if (empty($site_struct->domain)) ErrorManager::addError('Domain is required.');
	if (empty($site_struct->environment)) ErrorManager::addError('Environment is required.');
	if (empty($site_struct->key)) ErrorManager::addError('Key is required.');
	else $site_struct->key = strtolower($site_struct->key);
	if (strlen($site_struct->is_remote) == 0) $site_struct->is_remote = 0;
	
	// Models should only be installed if table prefix is specified
	$install_models = false;
	
	$create_www_alias = false;
	
/* Replaced by $site_struct->key: 	// If table_prefix is defined, check that all of the required directories are in place. */
#	if (!ErrorManager::anyDisplayErrors() && $table_prefix = Page::get('table_prefix')) {
	if (!ErrorManager::anyDisplayErrors() && !empty($site_struct->key)) {
		
		$site_struct->domain = strtolower($site_struct->domain);
		
		$table_prefix = $site_struct->key . '_';
		if (!is_writable($dir_fs_sites)) ErrorManager::addError('The sites directory (' . $dir_fs_sites . ') is not writable.');
		else {
			
			$create_www_alias = (count(explode('.', $site_struct->domain)) == 2);
			
			$xml_config = new CWI_XML_Traversal('config', null, array('version'=>1));
			$xml_database = new CWI_XML_Traversal('database');
			$xml_tables = new CWI_XML_Traversal('tables', null, array('prefix'=>$table_prefix));
			
			$xml_database->addChild($xml_tables);
			$xml_config->addChild($xml_database);
			
			if ($site_struct->is_remote == 1) { 
				NotificationManager::addMessage('Be sure to create a file at ~/config/config.xml with the following contents:<br />' . htmlentities($xml_config->render()));
			} else {
				$os_user = Page::get('os_user');
				$os_group = Page::get('os_group');
				#if (empty($os_user) || empty($os_group)) ErrorManager::addError('If table_prefix is specified, an OS user/group needs to be specified for the site\'s directory that will be created.');
				
				// Site/config directory values
				#$site_dir		= $dir_fs_sites . strtolower($site_struct->key) . '/';
				$site_dir		= $dir_fs_sites . $site_struct->domain . '/';
				$config_dir		= $site_dir . 'config/';
				$assets_dir		= $site_dir . 'assets/';
				$config_file		= $config_dir . 'config.xml';
				$config_contents	= $xml_config->render();
				$tmp_dir		= $site_dir . 'tmp/';
				$cache_dir		= $site_dir . 'tmp/cache/';

				if (!file_exists($site_dir)) {
					if (!@mkdir($site_dir,0777)) ErrorManager::addError('Could not create site directory at ' . $site_dir . '.');
					@chmod($site_dir, 0777);
				}
				// Create symbollic linked directory for www subdomain 
				if ($create_www_alias) {
					$link = $dir_fs_sites . 'www.' . $site_struct->domain;
					$symlink_result = symlink($site_dir, $link);
					var_dump($symlink_result);
					@chmod($link, 0777);
				}
				if (!ErrorManager::anyDisplayErrors()) {
				
					if (!file_exists($config_dir)) {
						if (!@mkdir($config_dir,0777,true)) ErrorManager::addError('Could not create config directory at ' . $config_dir . '.');
						@chmod($site_dir, 0770); // Ensure the 
					}
					
					if (!file_exists($assets_dir)) {
						if (!@mkdir($assets_dir, 0777, true)) ErrorManager::addError('Could not create assets directory at ' . $assets_dir . '.');
						@chmod($assets_dir, 0775);
					}
					
					// Create cache and tmp directory simultaneously
					if (!file_exists($cache_dir)) {
						if (!@mkdir($cache_dir, 0777, true)) ErrorManager::addError('Could not create cache directory at ' . $cache_dir . '.');
						@chmod($cache_dir, 0777);
						@chmod($tmp_dir, 0777);
					}
					
				}
				if (!ErrorManager::anyDisplayErrors() && !file_exists($config_file)) if (!@file_put_contents($config_file, $config_contents)) ErrorManager::addError('Could not create config file at ' . $config_file . '.');
				
				/*
				Unfortunately we cannot chown or chgrp without being a superuser :(
				$current_owner_info = posix_getpwuid(fileowner($site_dir));
				$current_group_info = posix_getgrgid(filegroup($site_dir));
				$current_owner = $current_owner_info['name'];
				$current_group = $current_group_info['name'];
				if ($current_owner != $os_user) if (!chown($site_dir, $os_user)) ErrorManager::addError('Unable to reassign site directory permission: ' . $current_owner . ' to requested owner: ' . $os_user);
				if ($current_group != $os_group) if (!@chgrp($site_dir, $os_group)) ErrorManager::addError('Unable to reassign site directory group from: '. $current_group . ' to requested group: ' . $os_group);
				*/
				if (!ErrorManager::anyDisplayErrors()) $install_models = true;
			}
		}
	}
	
	if (!ErrorManager::anyDisplayErrors()) {
		
		$site_struct = SiteLogic::save($site_struct);
		
		if ($create_www_alias) {
			
			$www_alias = 'www.' . $site_struct->domain;
			
			if (!$www_alias_site_struct = SiteLogic::getSiteByDomain($www_alias)) {
				
				$www_alias_site_struct = clone $site_struct;
				$www_alias_site_struct->id = null;
				SiteLogic::save($www_alias_site_struct);
				
			}
			
		}
		
		$required_models = array('asset_categories', 'asset_types', 'assets', 'content', 'controls', 'membership_parameters', 'memberships', 'memberships_roles', 'page_control_assets', 'page_controls', 'page_parameters', 'pages', 'permissions', 'roles', 'roles_permissions', 'sites', 'templates', 'object_templates');
		
		if ($install_models) {
			foreach($required_models as $required_model) {
				try {
					$model_plugin = CWI_MANAGER_SyncManager::getModelPluginByName($required_model);
				} catch (CWI_MANAGER_SyncException $e) {
					ErrorManager::addMessage($e->getMessage());
					break;
				}
				
				$table_creator = CWI_DB_TableCreatorFactory::createFromModel($model_plugin->getModel());
				
				$table_name = $table_prefix . $model_plugin->getModel()->getName();
				
				//not necessary: $xml_tables->addChild( new CWI_XML_Traversal('add', null, array('name'=>$model_plugin->getModel()->getTableName(), 'value'=>$model_plugin->getModel()->getTableName())) );
				
				if ($table_creator->tableExists($table_name)) {
					NotificationManager::addMessage('The table ' . $table_name . ' already existed and was not installed.');
				} else {
					try {
						$table_creator->createTable($table_name);
					} catch (Exception $e) {
						ErrorManager::addError($e->getMessage());
						break;
					}
					NotificationManager::addMessage('The table ' . $table_name . ' was created.');
				}
				#$model->setTableName( $table_prefix . $model->getTableName() );
				
				#echo '<pre>';
				#print_r(get_class_methods($model));
				#print_r($model);
				#echo '</pre>';exit;
				#echo 'Model: ' . $required_model . '<br />';
			}
		} else {
			Page::redirect('index.html?nomodelinstallation');
		}
		
		if ($install_models && !ErrorManager::anyDisplayErrors()) NotificationManager::addMessage('All required models/tables have been installed.');
	}	
} else {
	if (!is_writable($dir_fs_sites)) NotificationManager::addMessage('The sites directory is not writable.');
	if ($site_id = Page::get('siteid')) {
		$site_struct = SiteLogic::getSiteById($site_id);
	}
}

Page::setStruct('site', $site_struct);

$cbo_environment = Page::getControlById('cbo_environment');
$cbo_environment->setData($rs_environment);

if ($crm_enabled) {
	$cbo_company = Page::getControlById('cbo_company');
	$cbo_company->setData( CrmLogic::getCompanies() );
}

?>