<?php
FrameworkManager::loadLibrary('db.tablecreator');
FrameworkManager::loadLibrary('plugins.plugin');
class CWI_PLUGIN_ModelPlugin extends CWI_PLUGIN_Plugin {
	private $model;
	public function __construct($name, $model, $version=1) {
		parent::__construct($name, $version);
		$this->model = $model;
	}
	function getModel() { return $this->model; }
	function isInstalled() {
		$table_creator = CWI_DB_TableCreatorFactory::createFromModel($this->getModel());
		return $table_creator->tableExists( $this->getModel()->getTableName() );
	}
	
	/**
	 * @return int 0=Nothing to Install, 1=Installed, 2=Already Installed(nothing to do), <0 error
	 */
	protected function executeInstallation() {
		$table_creator = CWI_DB_TableCreatorFactory::createFromModel($this->getModel());
		try {
			if ($table_creator->createTable($this->getModel()->getTableName())) {
				return 1;
			} else {
				$this->setInstallationStatus('Could not create model (' . $this->getModel()->getName() . ') table: ' . $this->getModel()->getTableName());
				return -1;
			}
		} catch (Exception $e) {
			$this->setInstallationStatus('Unable to install model: ' . $this->getModel()->getName() . ' because: ' . $e->getMessage());
			return -1;
		}
		return -1;
	}
}

?>