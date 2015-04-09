<?php

#$dir = dirname(__FILE__) . DIRECTORY_SEPARATOR;

#require($dir . 'config.php');

require(dirname(dirname(__FILE__)) . DIRECTORY_SEPARATOR . 'lib' . DIRECTORY_SEPARATOR . 'config.php');
require(DIR_FS_CLI_LIB . 'argumentparser.php');
require(FILE_FRAMEWORK);

$args = new ArgumentParser($argv);

$app_directories = array('config', 'pages', 'controls', 'templates');

$dry_run = $args->isFlagSet('dry-run');
$create_db = $args->isFlagSet('db');
$verbose = $args->isFlagSet('v');

class SiteCreationLog {

	/**
	 * @property bool $realTimeOutput Whether to echo logged entries as they are added
	 **/
	private $realTimeOutput=false;
	private $eol = PHP_EOL;

	public function add($log) {
		if ($this->realTimeOutput) echo $log . $this->eol;
	}

	public function enableRealTimeOutput() { $this->realTimeOutput = true; }
	public function disableRealTimeOutput() { $this->realTimeOutput = false; }
}

$log = new SiteCreationLog();
if ($dry_run || $verbose) $log->enableRealTimeOutput();

#
# 1. Create app directories
#
if (count($app_directories) > 0) {

	$log->add('Creating app directories...');

	foreach ($app_directories as $app_dir) {

		$exists = file_exists($app_dir);

		$status = $exists ? 'Already exists' : 'Missing';

		if (!$dry_run && !$exists) {

			if (@mkdir($app_dir)) {

				$status = 'Created';

			}

			if (!file_exists($app_dir)) $status = 'Failed';
		}

		$log->add(DIRECTORY_SEPARATOR . $app_dir . DIRECTORY_SEPARATOR . ' - ' . $status);

	}
}

#
# 2. Create Config File
#
$config_file = 'config' . DIRECTORY_SEPARATOR . 'config.php';

if (!file_exists($config_file)) {

	$config = "<?php
	return array(
		'settings' => array(
			'general' => array(
				'DIR_FS_FRAMEWORK_APP' => __DIR__ . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR,
				'SITE_ENVIRONMENT' => 'development'
			)
		)";
	$config .= ",
		'database' => array(
			/* 'tableSettings' => array('prefix' => 'table_prefix_'),*/
			'connections' => array(
				'default' => array(
					'host' => 'localhost',
					'username' => 'username',
					'password' => 'password',
					'database' => 'database'
				)
			)
		)";
	$config .= "
	);";
	// Remove extra tab from formatting above so that code is align to left
	$config = preg_replace('#(\r?\n)\t#', '$1', $config) . PHP_EOL;

	file_put_contents($config_file, $config);
	$log->add('Update config/config.php with any necessary changes before re-running this script');
	exit;

}

#
# 3. Create database file
#
if ($create_db) {

	FrameworkManager::init(require $config_file, FRAMEWORK_MODE_CLI);

	if (ConnectionManager::hasConnection()) {

		$log->add('Found connection.  Creating tables.');

		$search_paths = PathManager::getPaths();

		// Create database tables
		foreach($search_paths as $search_path) {

			$dir_fs_models = $search_path . 'models' . DIRECTORY_SEPARATOR;
			$log->add('Model Path: ' . $dir_fs_models);

			FrameworkManager::loadLibrary('db.databasehelper');
			FrameworkManager::loadLibrary('xml.compile');
			FrameworkManager::loadLibrary('db.tablecreatorfactory');

			$dh = opendir($dir_fs_models);

			while ($file = readdir($dh)) {

				if (substr($file, -4) == '.xml') {
					$xml_model = CWI_XML_Compile::compile( file_get_contents($dir_fs_models . $file) );

					$model = CWI_DB_DatabaseHelper::convertXmlToModel($xml_model);

					$table_creator = CWI_DB_TableCreatorFactory::createFromModel($model);
					$table_result = 'N/A';

					if ($dry_run) {
						$table_result = $table_creator->tableExists() ? 'Exists' : 'Does not exist';
					} else {
						$table_creator->tableExists();
						$model_result = $table_creator->updateOrCreateTable();
						$table_result = $model_result->getType();
					}

					$log->add('Model: ' . $model->getName() . '; Table: ' . $model->getTableName() . ' = ' . $table_result);

				}
			}

		}

		// Make sure a default template is setup
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

		}

		#
		#
		#  Default Page Setup
		#
		#
		FrameworkManager::loadLogic('page');
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

		$log->add('Auto-discovering controls...');

		$rs_added_controls = ControlLogic::autoAddDiscoveredControls();
		$log->add('Found ' . $rs_added_controls->getCount() . ' controls');

		while ($control_struct = $rs_added_controls->getNext()) {
			$log->add('Adding control ' . $control_struct->label . ' (' . $control_struct->class_name . ')');
		}
		#############
		die('Yep, have connection');
	} else {
		die('No default database connection could be established' . PHP_EOL);
	}

} else {

	echo 'Skipping create database.  Call with --db to create database' . PHP_EOL;
}
