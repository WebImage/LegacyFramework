<?php

FrameworkManager::loadLibrary('db.tablecreator');

class CWI_DB_TableCreatorFactory {
	/**
	 * Create the appropriate SQL 
	 * @access static
	 */
	public static function createFromModel($model) {

		if (is_object($model) && !is_a($model, 'CWI_DB_Model')) throw new Exception('Invalid model passed to CWI_DB_TableCreatorFactory::createFromModel().');
		else if (is_string($model)) { // Look model up
			FrameworkManager::loadManager('sync');

			$model_plugin = CWI_MANAGER_SyncManager::getModelPluginByName($model);			
			$model = $model_plugin->getModel();

		}
		return new CWI_DB_MySqlTableCreator($model);
	}
	
}

?>