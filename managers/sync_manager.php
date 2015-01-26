<?php
/**
 *  This file duplicates a lot of functionality that exists in the /libraries/plugin/ directory - at some point, the classes on this page should be modified to instead use those files....
 **/
FrameworkManager::loadManager('cache');
FrameworkManager::loadLogic('remoterequest');
ConfigurationManager::set('SYNC_SERVER', 'https://sync.athenacms.com');
#ConfigurationManager::set('SYNC_SERVER_MODELS', '%SYNC_SERVER%/models/');

class CWI_MANAGER_SyncException extends Exception {}

class CWI_MANAGER_SyncManager {
	function getVersion() { return (double)1.0; }
	function login() {
	}

	public static function getSyncServers() {
		return explode(',', ConfigurationManager::get('SYNC_SERVER'));
	}
	
	public static function makeRequest($path, $cache_key, $cache_timeout=600, &$returning_cached_response=false) {
		FrameworkManager::loadManager('cache');
		$cache_key = 'response_' . $cache_key;
		$is_caching = false;
		if ($sync_cache_provider = CWI_MANAGER_CacheManager::getProvider('sync')) {
			$is_caching = true;
			
			if ($cached_object = $sync_cache_provider->getCacheByKey($cache_key, $cache_timeout)) {
				$returning_cached_response = true;
				return $cached_object;
			}	
		}
		
		try {
			ConfigurationManager::set('REMOTEREQUEST_IGNORESSLERRORS', 'true');
			if ($response = RemoteRequestLogic::getXmlResponse($path)) {

				if ($response_status = $response->getParam('status')) {
					if ($response_status == 'success') {
						if ($is_caching) {
							$sync_cache_provider->saveCacheByKey($cache_key, $response);
						}
						return $response;
					} else if ($response_status == 'failed') {
						if ($errors = $response->getPath('errors/error')) {
							$error_msg = '';
							foreach($errors as $error) $error_msg .= $error->getData();
							
							throw new CWI_MANAGER_SyncException('The request failed with the following errors: ' . $error_msg);
						} else {
							throw new CWI_MANAGER_SyncException('Request failed, but no error messages were included.');
						}
					} else {
						throw new CWI_MANAGER_SyncException('Unknown request status: ' . $response_status . '.');
					}
				} else {
					throw new CWI_MANAGER_SyncException('Missing status parameter in response.');
				}
			} else {
				throw new CWI_MANAGER_SyncException('Unable to retrieve remote request from: ' . $path);
			}
		} catch (CWI_XML_CompileException $e) {
			throw new CWI_MANAGER_SyncException('Unable to retrieve remote request from: ' . $path);
		}
	}
	
	public static function getPluginByLink($plugin_name, $link, $cache_timeout=600) {
		
		FrameworkManager::loadLibrary('sync.pluginhelper');
		try {
			$is_cache_response = null;
			$response = CWI_MANAGER_SyncManager::makeRequest($link, 'plugin_'.$plugin_name, $cache_timeout, $is_cache_response);
			if ($xml_plugin = $response->getPathSingle('plugin')) {
				$xml_plugin->removeParent();
				$plugin = CWI_SYNC_PluginHelper::convertXmlToPlugin($xml_plugin);
				
				if ($xml_requirements = $xml_plugin->getPathSingle('requirements')) {
					$requirement_collection = CWI_SYNC_PluginHelper::convertXmlRequirementsToPluginRequirementCollection($xml_requirements);
					while ($requirement = $requirement_collection->getNext()) {
						$plugin->addRequirement($requirement);
					}
					#$plugin_requirements = CWI_MANAGER_SyncManager::buildRequirementTree($requirement_collection);
					#while ($plugin_requirement = $plugin_requirements->getNext()) {
					#	$plugin->addPlugin($plugin_requirement);
					#}
				}
				
				return $plugin;
			} else {
				throw new CWI_MANAGER_SyncException('Missing plugin from response.');
			}
			
			#if ($xml_plug
		} catch (Exception $e) {
			throw new CWI_MANAGER_SyncException('Plugin error: ' . $plugin_name . '. ' . $e->getMessage());
		}
	}
	
	public static function isPluginInstalledOnSystem($plugin) {
		$plugin_path = ConfigurationManager::get('DIR_FS_PLUGINS') . $plugin;
		return file_exists($plugin_path);
	}
	
	/**
	 * Get the list of available plugins from the sync server
	 * @param int $timeout The number of seconds that sync server results will be stored
	 * @throws Exception if unable to makeRequest
	 * @return ResultSet a result set of available plugins
	 */
	public static function getAvailablePlugins($timeout=600) {
		if ($sync_server = ConfigurationManager::get('SYNC_SERVER')) {
			$request_path = $sync_server . '/plugins';
			$is_cached_response = null;
			try {
				$response = CWI_MANAGER_SyncManager::makeRequest($request_path, 'available_plugins', $timeout, $is_cached_response);
				if ($xml_plugins = $response->getPath('plugins/plugin')) {
					// Build result set of available plugins from the sync server
					$rs_plugins = new ResultSet();
					foreach($xml_plugins as $xml_plugin) {
						$plugin_struct = new stdClass();
						$plugin_struct->name = $xml_plugin->getParam('name');
						$plugin_struct->link = $xml_plugin->getParam('link');
						#$plugin_struct->in_database = (in_array($plugin_struct->name, $installed_plugin_stack)) ? 1:0;
						
						$plugin_struct->system_installed = CWI_MANAGER_SyncManager::isPluginInstalledOnSystem($plugin_struct->name) ? 1:0;
						
						$rs_plugins->add($plugin_struct);
					}
					return $rs_plugins;
				} else new ResultSet();
				
			} catch (Exception $e) {
				throw $e;
			}
			
		} else {
			throw new CWI_MANAGER_SyncException('Missing configuration value: SYNC_SERVER');
		}
	}
	/**
	 * Right now this is just an alias for getModelPluginByLink - in the future this may be a standalone function
	 */
	public static function getModelPluginByName($model, $cache_timeout=600) {
		return CWI_MANAGER_SyncManager::getModelPluginByLink($model, null, $cache_timeout);
	}
	/**
	 * Retrieves a model's structure by a definition retrieved from the sync server
	 * @param int $cache_time Time, in seconds, before cached version times out
	 * @throws CWI_MANAGER_SyncException if the <model /> is missing from the XML structure
	 * @throws CWI_MANAGER_SyncException if <response status="failed" />
	 * @throws CWI_MANAGER_SyncException if status is not defined in <response status="..." />
	 * @throws CWI_MANAGER_SyncException if unable to retrieve the model from the link provided.
	 * @throws CWI_MANAGER_SyncException if unable to convert the returned xml model into a Model object
	 * @return Model
	 */
	public static function getModelPluginByLink($model, $link, $cache_timeout=600) {
		#FrameworkManager::loadManager('cache');
		FrameworkManager::loadLibrary('sync.databasehelper');
		FrameworkManager::loadLibrary('sync.pluginhelper');
		FrameworkManager::loadLibrary('plugins.plugin.model');
		FrameworkManager::loadLibrary('plugins.pluginrequirement.model');
		$cache_key = 'model_' . $model;
				
		if ($sync_server = ConfigurationManager::get('SYNC_SERVER')) {
			if (empty($link)) {
				$request_path = $sync_server . '/models/?model=' . $model;
			} else {
				$request_path = $link;
			}
			
			$is_cached_response = null;
			try {

				$response = CWI_MANAGER_SyncManager::makeRequest($request_path, $cache_key, $cache_timeout, $is_cached_response);

				if ($xml_model = $response->getPathSingle('model')) {

					$xml_model->removeParent();
					
					$table_is_installed = (DatabaseManager::isTableDefined($xml_model->getParam('name')));
					if ($table_is_installed) {
						$xml_model->setParam('tableName', DatabaseManager::getTable($xml_model->getParam('name')));
					} else {
						$xml_model->setParam('tableName', DatabaseManager::getTablePrefix() . $xml_model->getParam('name'));
					}
					
					try {
						$model = CWI_SYNC_DatabaseHelper::convertXmlToModel( $xml_model );
					} catch (Exception $e) {
						throw new CWI_MANAGER_SyncException('Database helper error: ' . $e->getMessage());
					}
					
					if ($cache_model = CWI_MANAGER_CacheManager::getProvider('model')) {
						$cache_model->saveCacheByKey($model->getName(), $model);
					}
					
					$model_plugin = CWI_SYNC_PluginHelper::convertModelToPlugin($model);
					
					if ($xml_requirements = $xml_model->getPathSingle('requirements')) {
						$requirement_collection = CWI_SYNC_PluginHelper::convertXmlRequirementsToPluginRequirementCollection($xml_requirements);
						while ($requirement = $requirement_collection->getNext()) {
							$model_plugin->addRequirement($requirement);
						}
						#$plugin_requirements = CWI_MANAGER_SyncManager::buildRequirementTree($requirement_collection);
						#while ($plugin_requirement = $plugin_requirements->getNext()) {
						#	$model_plugin->addPlugin($plugin_requirement);
						#}
					}
						
					return $model_plugin;
				} else {
					throw new CWI_MANAGER_SyncException('Missing model from response.');
				}
			} catch (Exception $e) {
				throw $e;
			}
			
		} else {
			throw new CWI_MANAGER_SyncException('Missing configuration value: SYNC_SERVER');
		}
	}		
	
	public static function getAvailableModels($cache_timeout=600) {
		if ($sync_server = ConfigurationManager::get('SYNC_SERVER')) {
			$request_path = $sync_server . '/models';
			$is_cached_response = null;
			try {
				$response = CWI_MANAGER_SyncManager::makeRequest($request_path, 'available_models', $cache_timeout, $is_cached_response);
				if ($xml_models = $response->getPath('models/model')) {
					$rs_models = new ResultSet();
					foreach($xml_models as $xml_model) {
						$model_struct = new stdClass();
						$model_struct->name = $xml_model->getParam('name');
						$model_struct->link = $xml_model->getParam('link');
						$model_struct->table_defined = (DatabaseManager::isTableDefined($xml_model->getParam('name')));
						if ($model_struct->table_defined) {
							$model_struct->table = DatabaseManager::getTable($xml_model->getParam('name'));
						} else {
							//DatabaseManager::getTablePrefix()
							$model_struct->table = $xml_model->getParam('name');
						}
						$rs_models->add($model_struct);
					}
					return $rs_models;				
				} else {
					throw new CWI_MANAGER_SyncException('There were not any models included in the response from the sync server.');
				}
				
			} catch (Exception $e) {
				throw $e;
			}
			
		} else {
			throw new CWI_MANAGER_SyncException('Missing configuration value: SYNC_SERVER');
		}
	}
	
	/**
	 * Build the entire plugin tree and all sub requirements
	 * @param CWI_PLUGIN_Plugin $plugin The root plugin that we want to build all sub requirements from (self-looping)
	 * @param array $included_requirements Includes a list of all plugins that have already been included as part of this true.  Helps to prevent a circular loop where one plugin requires itself or a plugin that has already been included at a higher level
	 */
	public static function buildPluginInstallationTree($plugin, $cache_timeout=600) {//, $included_requirement=array()) {
		if (!is_a($plugin, 'CWI_PLUGIN_Plugin')) throw new CWI_MANAGER_SyncException('buildPluginInstallationTree($plugin) expecting object of type CWI_PLUGIN_Plugin.');
		
		FrameworkManager::loadLibrary('plugins.plugin.resourcefile');
		FrameworkManager::loadLibrary('plugins.plugincollection');
		$requirement_collection = $plugin->getRequirements();


		// Add plugin to $included_requirement stack so that sub plugins do not re-included a plugin that was already included at this higher level
		/*$plugin_key = get_class($plugin) . '_' . $plugin->getName(); // Include get_class to ensure that plugins of different types with the same name do not collide
		array_push($included_requirements, $plugin_key);*/
		
		#$plugin_collection = new CWI_PLUGIN_PluginCollection();
		#if ($requirement_collection->getCount() > 0) echo '<div style="border:1px solid #000;padding:20px;margin:20px;"> (TIMEOUT: ' . $cache_timeout . ')';
		
		while ($requirement = $requirement_collection->getNext()) {
			#$requirement->setPluginIndex( $plugin->getPluginCount() );
			switch (get_class($requirement)) {
				case 'CWI_PLUGIN_PluginPluginRequirement':
					// Get the basic plugin structure
					$required_plugin = CWI_MANAGER_SyncManager::getPluginByLink($requirement->getName(), $requirement->getLink(), $cache_timeout);

					// Add all plugin structure sub requirements
					$required_plugin = CWI_MANAGER_SyncManager::buildPluginInstallationTree($required_plugin, $cache_timeout);
					// Add the branch to the current list of [sub] plugins
					$plugin->addPlugin($required_plugin);
					break;
				case 'CWI_PLUGIN_ModelPluginRequirement':
					
					// Get the basic plugin structure
#$time0 = FrameworkManager::getTime();
					$required_plugin = CWI_MANAGER_SyncManager::getModelPluginByLink($requirement->getName(), $requirement->getLink(), $cache_timeout);
#$time1 = FrameworkManager::getTime();
#echo 'Class: ' . $required_plugin->getName() . '<br />';
#echo 'Link: ' . $requirement->getLink() . '<br />';
#echo 'Installed: ';
#if ($required_plugin->isInstalled()) echo 'Yes'; else echo 'No';
#echo '<br />';
					// Add all plugin structure sub requirements
					$required_plugin = CWI_MANAGER_SyncManager::buildPluginInstallationTree($required_plugin, $cache_timeout);
					// Add the branch to the current list of [sub] plugins
					$plugin->addPlugin($required_plugin);
#}
#$time2 = FrameworkManager::getTime();
#echo '<table border="1"><tr><td width="150">' . $required_plugin->getName() . '</td><td width="100">' . round($time1-$time0, 4) . '</td><td width="100">' . round($time2-$time1, 4) . '</td><td width="100">' . round($time2-$time0, 4) . '</td></tr></table>';
#echo '<hr>';
					break;
					
				case 'CWI_PLUGIN_ResourceFilePluginRequirement':
					$required_plugin = new CWI_PLUGIN_ResourceFilePlugin($requirement->getName(), $plugin->getBaseDir(), $requirement->getName(), $requirement->getLink());

					// Add the branch to the current list of [sub] plugins
					$plugin->addPlugin($required_plugin);
					break;
				default:
					throw new CWI_MANAGER_SyncException('Uknown requirement type (' . get_class($requirement) . ') encountered while building requirement tree.');		
			}
		}
#if ($requirement_collection->getCount() > 0) echo '</div>';
		return $plugin;
	}
	
	/**
	 * Takes the results from buildPluginTree and builds a flat ResultSet of all plugins that need to be installed
	 */
	public static function buildInstallationManifestResultSet($plugin_tree, $level=0) {
		if (!is_a($plugin_tree, 'CWI_PLUGIN_Plugin')) throw new CWI_MANAGER_SyncException('buildInstallationManifestResultSet($plugin_tree) expecting object of type CWI_PLUGIN_Plugin.');
		$rs_installation = new ResultSet();
		$plugins = $plugin_tree->getPlugins();
		
		$requirements = $plugin_tree->getRequirements();
		while ($requirement = $requirements->getNext()) {
			$associated_plugin = $plugins->getAt($requirements->getCurrentIndex());
			
			$struct			= new stdClass();
			$struct->name		= $requirement->getName();
			$struct->level		= $level;
			$struct->description	= $requirement->getDescription();
			$struct->installed	= ($associated_plugin->isInstalled()) ? 1:0;
			$rs_installation->add($struct);
			
			$child_requirements = CWI_MANAGER_SyncManager::buildInstallationManifestResultSet($associated_plugin, $level+1);
			$rs_installation->merge($child_requirements);
		}		
		
		return $rs_installation;
	}
	
	public static function getPackageModels($package, $cache_timeout=600) {
		
		$models = new ResultSet();
		
		if ($sync_server = ConfigurationManager::get('SYNC_SERVER')) {
			
			$request_path = $sync_server . '/models/' . $package . '/';
			$is_cached_response = null;
			try {
				$response = CWI_MANAGER_SyncManager::makeRequest($request_path, 'package_models', $cache_timeout, $is_cached_response);

				FrameworkManager::loadLibrary('sync.databasehelper');

				if ($xml_models = $response->getPath('models/model')) {
					$rs_models = new ResultSet();
					foreach($xml_models as $xml_model) {
						$xml_model->removeParent();
						$models->add(CWI_SYNC_DatabaseHelper::convertXmlToModel( $xml_model ));
					}
				} else {
					throw new CWI_MANAGER_SyncException('There were not any models included in the response from the sync server.');
				}
				
			} catch (Exception $e) {
				throw $e;
			}
			
		} else {
			throw new CWI_MANAGER_SyncException('Missing configuration value: SYNC_SERVER');
		}
		return $models;
		
	}
	
	public static function syncModelPackage($package, $cache_timeout=600) {
		
		$rs_models = self::getPackageModels($package);
		$results = new Collection();
		while ($model = $rs_models->getNext()) {
			$results->add(CWI_MANAGER_SyncManager::syncModel($model));
		}
		return $results;
	}
	/**
	 * @param Model|string $model a model object or model string
	 * @return CWI_DB_ModelResult
	 */
	public static function syncModel($model) {
		FrameworkManager::loadLibrary('db.tablecreator');
		$table = CWI_DB_TableCreatorFactory::createFromModel($model);
		$result = $table->updateOrCreateTable();
		return $result;
	}
}

?>