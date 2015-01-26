#!/usr/bin/env php
<?php
ini_set('display_errors', 1);
require(dirname(dirname(__FILE__)) . '/lib/config.php');
require(DIR_FS_CLI_LIB . 'tableformat.php');
require(DIR_FS_CLI_LIB . 'argumentparser.php');

#require(FILE_FRAMEWORK);

define('SITE_MODE_CREATE', 'create');
define('SITE_MODE_UPDATE', 'update');

$args = new ArgumentParser($argv);

$possible_flags = array(
	's'		=> array(
				'help_text' => 'Domain name (e.g. domain.com)'
				),
	'n'		=> array(
				'help_text' => 'Website name'
				),
	'e'		=> array(
				'help_text' => 'Environment Possible values = development, staging, production'
				),
	'r'		=> array(
				'help_text' => 'Is remote? Possible values = y | n'
				),
	'p' 		=> array(
				'help_text' => 'Table prefix'
	)
	/*'sync-db'	=> false, 		'Sync database (only works if using --update)'*/
	);

$site_domain	= $args->getFlag('s');
$site_name		= $args->getFlag('n', $site_domain);
$site_environment	= $args->getFlag('e', 'production');
$site_is_remote	= ($args->getFlag('r') == 'y');
$table_prefix	= $args->getFlag('p');

$valid_request = true;
$help_text = '';

#$sync_db = true;//= ( ($mode == SITE_MODE_CREATE) || ($mode == SITE_MODE_UPDATE && $args->getFlag('sync-db') == 'y') );

$help_text .= 'Usage: ' . $args->getCommand() . ' [OPTION]:' . PHP_EOL;
$help_text .= 'Creates or updates a content management system website.' . PHP_EOL . PHP_EOL;
$help_text .= 'Arguments: ' . PHP_EOL;

foreach($possible_flags as $possible_flag=>$flag_info) {
	
	##if ($valid_request && $required) if (!$args->isFlagSet($possible_flag)) $valid_request = false;//die("Missing flag -$possible_flag - $description\n");
	#if (!$args->isFlagSet($possible_flag)) $valid_request = false;//die("Missing flag -$possible_flag - $description\n");
	$help_text .= str_repeat(' ', 2) . '-' . str_pad($possible_flag, 8) . '   ' . $flag_info['help_text'] . PHP_EOL;
	
}
$help_text .= PHP_EOL;

if (empty($site_domain)) $valid_request = false;

if (!$valid_request) {
	echo $help_text;
}

$rows = array(
	array(
		'Command' => $args->getCommand(),
		'Domain' => $args->getFlag('s', '(not set)'),
		'Name' => $args->getFlag('n', '(not set)'),
		/*'Key' => $args->getFlag('k', '(not set)'),*/
		'Environment' => $args->getFlag('e', '(not set)'),
		'Is Remote' => $args->getFlag('r', '(not set)')
	)
);
echo table_format_rows($rows);


if ($valid_request) {
	require(FILE_FRAMEWORK);
	
	FrameworkManager::init(FRAMEWORK_MODE_CLI);
	
	// Check if site already exists
	FrameworkManager::loadLogic('site');
	#FrameworkManager::loadLogic('uuid');
	
	class SiteCreationLog extends Collection {
		/**
		 * @property bool $realTimeOutput Whether to echo logged entries as they are added
		 **/
		private $realTimeOutput=false;
		private $eol = PHP_EOL;
		
		public function add($log) {
			if ($this->realTimeOutput) echo $log . $this->eol;
			parent::add($log);
		}
		
		public function enableRealTimeOutput() { $this->realTimeOutput = true; }
		public function disableRealTimeOutput() { $this->realTimeOutput = false; }
	}
	
	class Site2Logic {
		
		const ENVIRONMENT_PRODUCTION	= 'production';
		const ENVIRONMENT_STAGING	= 'staging';
		const ENVIRONMENT_DEVELOPMENT	= 'development';
		const ENVIRONMENT_DELETED	= 'deleted';
		
		private static function restoreSiteState(SiteCreationLog $log, $site_domain, $restore_current_path=null) {
			if (empty($site_domain)) $site_domain = null;
			$log->add('Restoring site state for: ' . (is_null($site_domain) ? 'No site' : $site_domain));
			FrameworkManager::init(FRAMEWORK_MODE_CLI, $site_domain);
			if (!empty($restore_current_path)) chdir($restore_current_path);
			// Return false to allow chaining of other methods
			return false;
		}
		
		public static function createOrUpdateSite(SiteCreationLog $log, $site_domain, $name, $environment=self::ENVIRONMENT_PRODUCTION, $is_remote=false) {
			
			FrameworkManager::loadStruct('site');
			
			$create_site = true;
			
			$log->add('Checking ' . $site_domain);
			
			if ($site = SiteLogic::getSiteByDomain($site_domain)) {
				
				$log->add($site_domain . ' exists');
				
				$create_site = false;
				
				if ($site->enable != 1) {
					$site->enable = 1;
					SiteLogic::save($site);
				}
				#echo $site_domain . ' has already been created.  Try using --update instead.' . PHP_EOL;
					
				// Automatically prefil other values
				#$args->setFlag('n', $site->name);
				#$args->setFlag('k', strtolower($site->key));
				#$args->setFlag('e', $site->environment);
				#$args->setFlag('r', ($site->is_remote==1) ? 'y':'n');
			
			} else {
				
				$site = new SiteStruct();
				$site->domain		= $site_domain;
				$site->name		= $name;
				$site->environment	= $environment;
				$site->is_remote	= $is_remote ? '1' : '0';
				
				SiteLogic::save($site);
				
				// $site->id should be defined
				if (empty($site->id)) {
					$log->add($site_domain . ' could not be created.  Usually this is because the database table does not exist: ' . DatabaseManager::getTable('sites'));
					return self::restoreSiteState($log, $site_domain);
				} else {
					$log->add($site_domain . ' was created in ' . DatabaseManager::getTable('sites'));
				}
						
			}
		
			if (!isset($site->key) || empty($site->key)) {
			
				#$site->key = UuidLogic::v4();
				$site->key = 'site' . $site->id;
				SiteLogic::save($site);
				
				$log->add('Generating new site key: ' . $site->key);
		
			}
			
			// Make sure sites directory exists
			if (!file_exists(ConfigurationManager::get('DIR_FS_FRAMEWORK_SITES'))) {
				
				// Try to create directory if it does not exist
				if (!@mkdir(ConfigurationManager::get('DIR_FS_FRAMEWORK_SITES'), 0777, true)) {
					
					$log->add('Unable to creates sites directory at: ' . ConfigurationManager::get('DIR_FS_FRAMEWORK_SITES'));
					
					return self::restoreSiteState($log, $site_domain);
					
				}
			}
			
			// Establish site home
			$dir_site = ConfigurationManager::get('DIR_FS_FRAMEWORK_SITES') . $site->domain . '/';
			$config_file = $dir_site . 'config/config.xml';
			
			if (!file_exists($dir_site)) {
				
				$log->add('Site directory does not exist: ' . $dir_site);
				
				if (@mkdir($dir_site, 0777, true)) {
					$log->add('Created site directory');
					@mkdir($dir_site . 'assets', 0777, true);
					@mkdir($dir_site . 'tmp/cache', 0777, true);
				} else {
					
					$log->add('Unable to create site directory');
					return self::restoreSiteState($log, $site_domain);
				}
				
			}
			
			$owning_user = fileowner(ConfigurationManager::get('DIR_FS_FRAMEWORK_SITES'));
			$owning_group = filegroup(ConfigurationManager::get('DIR_FS_FRAMEWORK_SITES'));
			
			function recursive_chown_chgrp($dir, $uid, $gid,$level=0) {
				$dh = opendir($dir);
				
				if ($level == 0) {
					chown($dir, $uid);
					chgrp($dir, $gid);
					chmod($dir, 0775);
				}
				while ($file = readdir($dh)) {
					
					if (!in_array($file, array('.','..'))) {
						
						if (filetype($dir.$file) == 'dir') {
							recursive_chown_chgrp($dir . $file . '/', $uid, $gid, $level+1);
						}
						
						chown($dir.$file, $uid);
						chgrp($dir.$file, $gid);
						chmod($dir.$file, 0775);
						
					}
					
				}
			}
			recursive_chown_chgrp($dir_site, $owning_user, $owning_group);
			
			/*
			
			function recurse_chown_chgrp($mypath, $uid, $gid) 
{ 
    $d = opendir ($mypath) ; 
    while(($file = readdir($d)) !== false) { 
        if ($file != "." && $file != "..") { 

            $typepath = $mypath . "/" . $file ; 

            //print $typepath. " : " . filetype ($typepath). "<BR>" ; 
            if (filetype ($typepath) == 'dir') { 
                recurse_chown_chgrp ($typepath, $uid, $gid); 
            } 

            chown($typepath, $uid); 
            chgrp($typepath, $gid); 

        } 
    } 

 } 

			*/
			
			// Create config for site
			if (!file_exists($config_file)) {
				
				$log->add('Creating config file: ' . $config_file);
				
				$exec_config = ConfigurationManager::get('DIR_FS_FRAMEWORK_BASE') . 'cli/sites/config.php';
				
				$site_config_output = exec('php ' . $exec_config . ' --config-file ' . $config_file . ' --table-prefix ' . strtolower($site->key) . '_');
			
				if (strpos(strtolower($site_config_output), 'success') === false) {
					
					$log->add('Unable to create config file');
					return self::restoreSiteState($log, $site_domain);
					
				}
				
			}
			
			#
			#
			# Re-initialize with domain
			#
			#
			
			$log->add('Re-initializing framework as ' . $site_domain);
			
			FrameworkManager::init(FRAMEWORK_MODE_CLI, $site_domain);

			if (strlen(ConfigurationManager::get('DOMAIN')) == 0) {
				
				$log->add('DOMAIN not defined in configuration, meaning something may have failed');
				return self::restoreSiteState($log, $site_domain);
			}
			
			
			// Sync core database structure
			FrameworkManager::loadManager('sync');
			
			$log->add('Syncing database models.  This may take a minute...');

			// CWI_DB_ModelResult
			$sync_db = true;
			
			if ($sync_db) {
				$model_results = new Collection();
				$rows = array();
				
/**
$table = CWI_DB_TableCreatorFactory::createFromModel($model);
$result = $table->updateOrCreateTable();
return $result;
*/
				FrameworkManager::loadLibrary('db.databasehelper');
				FrameworkManager::loadLibrary('xml.compile');
				FrameworkManager::loadLibrary('db.tablecreatorfactory');
				
				$files = array();
				$dir_fs_models = dirname(dirname(dirname(__FILE__))) . DIRECTORY_SEPARATOR . 'models' . DIRECTORY_SEPARATOR;
				$dh = opendir($dir_fs_models);
				
				while ($file = readdir($dh)) {
					if (substr($file, -4) == '.xml') {
						$xml_model = CWI_XML_Compile::compile( file_get_contents($dir_fs_models . $file) );
						
						$model = CWI_DB_DatabaseHelper::convertXmlToModel($xml_model);
						$table_creator = CWI_DB_TableCreatorFactory::createFromModel($model);
						$model_result = $table_creator->updateOrCreateTable();
						$rows[] = array(
							'Model' => $model_result->getModel()->getName(),
							'Table' => $model_result->getModel()->getTableName(), 
							'Result' => $model_result->getType()
						);
					}
				}
			
				$log->add(table_format_rows($rows));
				
				
			}

			FrameworkManager::loadLogic('page');
			FrameworkManager::loadLogic('template');
			
			$page_templates = TemplateLogic::getTemplates('Page');
			$log->add('# of page templates: ' . $page_templates->getCount());
			
			$page_template_id = 0;
			
			#
			#
			# Default Template Setup
			#
			#
			 
			// Create default template link, if necessary
			if ($page_templates->getCount() == 0) {
				
				FrameworkManager::loadStruct('template');
				
				$template_struct = new TemplateStruct();
				$template_struct->file_src = '~/templates/default.tpl';
				$template_struct->name = 'Default';
				$template_struct->type = 'Page';
				
				$template_struct = TemplateLogic::save($template_struct);
				$page_template_id = $template_struct->id;
			} else {
				while ($template_struct = $page_templates->getNext()) {
					if ($template_struct->file_src == '~/templates/default.tpl') {
						$page_template_id = $template_struct->id;
						break;
					}
				}
			}
			
			if (empty($page_template_id)) {
				
				$log->add('Unable to find or create default default template');
				return self::restoreSiteState($log, $site_domain);
				
			}
			
			#
			#
			#  Default Page Setup
			#
			#
			
			// Check that home page exists
			if (PageLogic::getPageByUrl('/index.html')) {
				
				$log->add('Home page (/index.html) exists');
				
			} else {
				
				$log->add('Creating home page (/index.html)');
				$page_title	= 'Welcome to ' . $site->name;
				$page_url	= '/index.html';
				$status		= PageLogic::STATUS_PUBLISHED;
				$parent_id	= 0;
				$template_id	= $page_template_id;
				
				$page_struct = PageLogic::createQuickSection($page_title, $status, $parent_id, $template_id, $page_url);
				
				if (empty($page_struct->id)) {
					$log->add('Unable to create default page');
					return self::restoreSiteState($log, $site_domain);
				}
			}
			
			#
			#
			# Default Admin User / Role Setup
			#
			#
			
			// Check that admin role exists
			FrameworkManager::loadLogic('role');
			if (!$role_id = RoleLogic::getRoleIdByName('AdmBase')) {
				FrameworkManager::loadStruct('role');
				
				$role_struct = new RoleStruct();
				$role_struct->description = 'Basic administration';
				$role_struct->name = 'AdmBase';
				//$role_struct->visible = 1;
				$role_struct = RoleLogic::save($role_struct);
				$role_id = $role_struct->id;
			}
			
			if (empty($role_id)) {
				
				$log->add('Unable to find or create default AdmBase role');
				return self::restoreSiteState($log, $site_domain);
				
			}
			
			// Check that admin user exists
			FrameworkManager::loadLogic('membership');
			FrameworkManager::loadStruct('membership');
			$create_admin = false;
			
			if (MembershipLogic::getNumMemberships() == 0) {
				
				$create_admin = true;
				$log->add('No memberships were found');
				
			} else {
				
				$dao = new DataAccessObject();
				$query = $dao->selectQuery("
						  SELECT *
						  FROM `" . DatabaseManager::getTable('memberships') . "`
						  ", 'MembershipStruct');
				
				if ($query->getCount() > 20) {
					$log->add('A problem occurred because too many memberships (" . $query->getCount() . ") were found to try and locate a default admin user.  Use the admin to create a user');
					return self::restoreSiteState($log, $site_domain);
				}
				
				// Iterate through existing memberships to check if any of them are admin users
				$create_admin = true;
				$continue = true;
				
				$log->add('Searching existing members for AdmBase (' . $role_id . ')...');
				
				while ($membership_struct = $query->getNext()) {
					
					$log->add('Checking user ' . $membership_struct->username . '...');
					
					if ($member_roles = RoleLogic::getAllRolesForUser($membership_struct->id)) {
						
						while ($member_role = $member_roles->getNext()) {
							
							$log->add('Cross checking role ' . $member_role->name);
							
							// Found member in AdmBase role		
							if ($member_role->id == $role_id) {
								
								$log->add('... found');
								$create_admin = false;
								$continue = false;
								break;
								
							}
							
						}
						
					} else {
						
						$log->add($membership_struct->username . ' does not have any roles');
						
					}
					
					if (!$continue) break;
					
				}
				
			}
			
			if ($create_admin) {
				
				$membership_struct = new MembershipStruct();
				$membership_struct->approved	= 1;
				$membership_struct->approved_by	= 0;
				$membership_struct->comment	= 'Generated';
				$membership_struct->enable	= 1;
				$membership_struct->password	= 'password';
				$membership_struct->username	= 'admin';
				
				$membership_struct = MembershipLogic::save($membership_struct);
				$debug = DebugDbManager::getMessages();
				#while ($d = $debug->getNext()) {
					#echo 'DEBUG: ' . $d . PHP_EOL;
				#}
				#print_r($membership_struct);	
				$log->add('Create admin user ' . $membership_struct->id . ' to role: ' . $role_id);
				
				RoleLogic::addUserToRole($membership_struct->id, $role_id);
				
			} else {
				
				$log->add('Admin user already exists');
				
			}
			
			#
			#
			# Default Page Controls
			#
			#
			
			FrameworkManager::loadLogic('control');
			
			/*
			FrameworkManager::loadStruct('control');
			$required_controls = array(
								  // file_src, label, found 
				'EditTextControl'	=> array('~/controls/edittext/edittext.php', 'Text', false)
			);
		
			$control_src_index	= 0;
			$control_label_index	= 1;
			$control_found_index	= 2;
			
			
			$controls = ControlLogic::getControls();
			while ($control = $controls->getNext()) {
				
				if (isset($required_controls[$control->class_name])) {
					$required_controls[$control->class_name][$control_found_index] = true;
					
				}
				
			}
			
			echo 'Required controls: ' . (count($required_controls) == 0 ? 'None':'') . PHP_EOL;
			foreach($required_controls as $control_class_name=>$control_details) {
				echo 'Control: ' . $control_class_name . ' [' . ($required_controls[$control_class_name][$control_found_index] ? 'INSTALLED':'NOT INSTALLED') . ']';
				if (!$required_controls[$control_class_name][$control_found_index]) {
		
					$control_struct = new ControlStruct();
					$control_struct->class_name	= $control_class_name;
					$control_struct->enable		= 1;
					$control_struct->file_src	= $control_details[$control_src_index];
					$control_struct->label		= $control_details[$control_label_index];
					echo ' [INSTALLED]';
					ControlLogic::save($control_struct);
				}
				echo PHP_EOL;
			}
			**/
			$log->add('Auto-discovering controls...');
			
			$rs_added_controls = ControlLogic::autoAddDiscoveredControls();
			$log->add('Found ' . $rs_added_controls->getCount() . ' controls');
			
			while ($control_struct = $rs_added_controls->getNext()) {
				$log->add('Adding control ' . $control_struct->label . ' (' . $control_struct->class_name . ')');
			}
		}
	}
	
	$log = new SiteCreationLog();
	$log->enableRealTimeOutput();
	
	$valid = false;
	
	/*if ($args->isFlagSet('debug')) {
		echo PHP_EOL . PHP_EOL . str_repeat('-', 50) . PHP_EOL;
		echo 'Switching to create' . PHP_EOL;
		sleep(1);
	}*/
	
	if ($args->isFlagSet('create')) { // c=create d=debug
		if (empty($table_prefix)) {
			echo '-p for table prefix must be specified' . PHP_EOL;
		} else {
			Site2Logic::createOrUpdateSite($log, $site_domain, $site_name, $site_environment, $site_is_remote);
			$valid = true;
		}
	}
	
	if (!$valid) {
		die('Use -create to create or -debug to debug' . PHP_EOL);
	}
	
	
} else die('Cannot continue - missing at least one parameter' . PHP_EOL);

?>