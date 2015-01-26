<?php

/**
 * 09/06/2010	(Robert Jones) Added
 */

class CWI_MANAGER_ModelManager {
	/*
	public static function getCacheKeyFromModelName($name) {
		return 'model_' . $name;
	}
	*/
	
	/**
	 * @param string $model_name The model name to be looked up
	 * @param boolean $sync_fallback Whether to use sync if all other methods fail - defaults to false because it could potentially cause delays for retrieval
	 */
	public static function getModel($model_name, $sync_fallback=false, $allow_caching=true) {
		#include(DIR_FS_FRAMEWORK_BASE . 'managers/cache_manager.php');

		FrameworkManager::loadBaseManager('cache'); // Time: 0.0013
		FrameworkManager::loadBaseLibrary('db.model'); // Time: 0.003+
		// Check if model is cached and return object if true
		if ($allow_caching && $cache_model = CWI_MANAGER_CacheManager::getProvider('model')) {
			#if ($model = $cache_model->getCacheByKey(self::getCacheKeyFromModelName($model_name))) {

			if ($model = $cache_model->getCacheByKey($model_name, 30)) { // Cached for 30 seconds
				if (is_a($model, 'CWI_DB_Model')) {
					return $model;
				}
			}
		}
		
		
		// Next, check existing database table
		$valid = true;
		FrameworkManager::loadLibrary('db.databasehelper');
		try {
			$model = CWI_DB_DatabaseHelper::createModelFromTableKey($model_name);
		} catch (Exception $e) {
			$valid = false;
		}
		if ($valid) {
			// Cache resulting model
			self::cacheModel($model);
			return $model;
		}
				
		// Check plugins
		# Do something
		
		if ($sync_fallback) {
			// Finally, try getting from sync
			$valid = true;
			FrameworkManager::loadManager('sync');
			try {
				$model_result = CWI_MANAGER_SyncManager::syncModel($model_name);
			} catch (Exception $e) {
				$valid = false;
			}
			
			if ($valid) {
				self::cacheModel($model_result->getModel());
				return $model_result->getModel();
			} else return false;
		}
		return false;
	}
	
	public static function cacheModel($model) {
		if ($cache_model = CWI_MANAGER_CacheManager::getProvider('model')) {
			/*
			$key = self::getCacheKeyFromModelName($model->getName());
			$cache_model->saveCacheByKey($key, $model);
			*/
			$cache_model->saveCacheByKey($model->getName(), $model);
		}
		
	}
	public static function deleteCachedModel($model) {
		if ($cache_model = CWI_MANAGER_CacheManager::getProvider('model')) {
			/*
			$key = self::getCacheKeyFromModelName($model->getName());
			$cache_model->deleteCacheByKey($key);
			*/
			$cache_model->deleteCacheByKey($model->getName());
		}
	}
}

?>