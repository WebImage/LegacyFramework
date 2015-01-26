<?php
FrameworkManager::loadManager('sync');
FrameworkManager::loadLibrary('plugins.plugin');

class CWI_PLUGIN_StandardPlugin extends CWI_PLUGIN_Plugin {
	private $friendlyName;
	private $baseDir;
	private $xmlConfig;
	
	public function __construct($name, $base_dir, $friendly_name, $version=1) {
		parent::__construct($name, $version);
		$this->friendlyName = $friendly_name;
		$this->baseDir = $base_dir;
		$this->xmlConfig = new CWI_XML_Traversal('plugin');
	}
	
	public function getFriendlyName() { return $this->friendlyName; }
	public function getBaseDir() { return $this->baseDir; }
	public function getXmlConfig() { return $this->xmlConfig; }
	
	public function setXmlConfig($xml_plugin) {
		$this->xmlConfig = $xml_plugin;
	}
	
	private function getLocalModels() {
		
		$models = array();
		
		FrameworkManager::loadLibrary('sync.databasehelper');
		FrameworkManager::loadLibrary('xml.compile');
		
		$models_dir = $this->getBaseDir() . 'models/';
		
		if (file_exists($models_dir)) {
			
			$dh = opendir($models_dir);
			
			while ($file = readdir($dh)) {
				
				if (filetype($models_dir . $file) == 'file') {
					
					if (substr($file, -4) == '.xml') {
						
						$model_file_contents = file_get_contents($models_dir . $file);
						
						$xml_model = CWI_XML_Compile::compile($model_file_contents);
						
						$model = CWI_SYNC_DatabaseHelper::convertXmlToModel( $xml_model );
						array_push($models, $model);
						
					}
				}
			}
		}
		
		return $models;
	}
	
	private function checkLocalModelsInstalled() {
		
		FrameworkManager::loadLibrary('db.tablecreatorfactory');
		
		$models = $this->getLocalModels();
		
		foreach($models as $model) {
			
			$table_creator = CWI_DB_TableCreatorFactory::createFromModel($model);
			#$table_creator->updateOrCreateTable();
			if (!$table_creator->tableExists()) return false;
			
		}
		
		return true;
	}
	
	private function installLocalModels() {
		
		FrameworkManager::loadLibrary('db.tablecreatorfactory');
		
		$models = $this->getLocalModels();
		
		foreach($models as $model) {
			
			$table_creator = CWI_DB_TableCreatorFactory::createFromModel($model);
			$table_creator->updateOrCreateTable();
			
		}
		
		return true;
		
	}
	
	private function checkPermissionsInstalled() {
		
		FrameworkManager::loadLogic('permission');
		
		$xml_plugin = $this->getXmlConfig();
		
		if ($xml_permissions = $xml_plugin->getPath('permissions/add')) {
			
			foreach($xml_permissions as $xml_permission) {
				
				if (!PermissionLogic::permissionExists($xml_permission->getParam('permission'))) return false;
				
			}
			
		}
		
		return true;
	}
		
	private function installPermissions() {
		
		FrameworkManager::loadLogic('permission');
		
		$xml_plugin = $this->getXmlConfig();
		
		if ($xml_permissions = $xml_plugin->getPath('permissions/add')) {
			
			foreach($xml_permissions as $xml_permission) {
				
				// createPermission will automatically create or update a permission
				PermissionLogic::createPermission($xml_permission->getParam('permission'), $xml_permission->getParam('description'));
				
			}
			
		}
		
		return true;
	}
	
	public function isInstalled() {
		if (!CWI_MANAGER_SyncManager::isPluginInstalledOnSystem($this->getName())) return false;
		if (!self::checkLocalModelsInstalled()) return false;
		if (!self::checkPermissionsInstalled()) return false;
		
		return true;
	}
	
	public function executeInstallation() {
		
		FrameworkManager::loadLibrary('event.manager');
		
		// Let everybody know that we are about to start the plugin installation process
		CWI_EVENT_Manager::trigger($this, 'executeInstallation');
		
		$this->installPermissions();
		$this->installLocalModels();
				
		// Let everybody know that the installation process has been completed
		CWI_EVENT_Manager::trigger($this, 'executeInstallationComplete');
		
		return 0;
	}
	
}

?>