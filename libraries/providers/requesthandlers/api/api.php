<?php

class ApiRequestHandler extends RequestHandler {
	public function canHandleRequest($internal_url=null) {
		return true;		
	}
	private function verifyAuthKey($auth_key) {
		if (is_null($auth_key) || is_bool($auth_key)) return false;
		return true;
	}
	private function getErrorXml($message) {
		return '<response><valid>false</valid><error>' . htmlentities($message) . '</error></response>';
	}
	private function getInvalidAuthorizationKeyXml() {
		return $this->getErrorXml('Invalid request');
	}
	function render() {
		FrameworkManager::loadManager('sync');
		
		$servers = CWI_MANAGER_SyncManager::getSyncServers();
		
		$action = $this->getPageRequest()->get('action');
		$auth_key = $this->getPageRequest()->get('authkey');
		
		$supported_actions = array('requestaccess', 'getconfigvalue', 'getdatabasetables', 'getversion', 'syncmodel', 'syncmodelpackage', 'getsupportedactions');
		/**
		 * Additional Actions:
		 * getplugins
		 * addupdatetable
		 * gettable
		 * installplugin
		 */
		if (empty($action)) return $this->getErrorXml('Missing action');
		else if (!in_array($action, $supported_actions)) return $this->getErrorXml('Unsupported action: ' . $action);
		
		switch ($action) {
			case 'requestaccess':
				FrameworkManager::loadManager('cache');
				if ($content_cache = CWI_MANAGER_CacheManager::getProvider('content')) {
					$cache_key = 'syncrequestaccess';
					// ConfigurationLogic::getConfigValue('VAR', 'config.path.to')
					// ConfigurationLogic::getConfigValue('SYNCREQUESTACCESS', 'admin', 'hidden', 'unlocked');
					if (!$key = $content_cache->getCacheByKey($cache_key, 5)) {
						FrameworkManager::loadLogic('uuid');
						$key = UuidLogic::v4();
						$content_cache->saveCacheByKey($cache_key, $key);
					}
					
					return '<response><valid>true</valid><key>' . $key . '</key></response>';
				}
				return $this->getErrorXml('Unable to create local key');
				break;
			case 'getdatabasetables':
				if ($this->verifyAuthKey($auth_key)) {
					
				} else return $this->getInvalidAuthorizationKeyXml();
				break;
			case 'getversion':
				if ($this->verifyAuthKey($auth_key)) {
					return '<response><valid>true</valid><version>' . floatval(ConfigurationManager::get('VERSION')) . '</version></response>';
				} else return $this->getInvalidAuthorizationKeyXml();
				break;
			case 'getconfigvalue':
				if ($this->verifyAuthKey($auth_key)) {
					$group = null;
					$config_name = Page::get('var');
					if (preg_match('/(.+)\.(.+)/', $config_name, $matches)) {
						$group = $matches[1];
						$config_name = $matches[2];
					}
					$config_value = ConfigurationManager::get($config_name, $group);
					if ($config_value === false) {
						return $this->getErrorXml('The configuration value ' . $config_name . ' is not set');
					} else {
						return '<response><valid>true</valid><config name="' . $config_name . '" group="' . $group . '" value="' . htmlentities($config_value) . '" /></response>';
					}
				} else return $this->getInvalidAuthorizationKeyXml();
			case 'getsupportedactions':
				if ($this->verifyAuthKey($auth_key)) {
					$output = '<response><valid>true</valid><supportedActions>';
					foreach($supported_actions as $supported_action) {
						$output .= '<action name="' . $supported_action . '" />';
					}
					$output .= '</supportedActions></response>';
					return $output;	
				} else return $this->getInvalidAuthorizationKeyXml();
								
				break;
			case 'syncmodelpackage':
				if ($this->verifyAuthKey($auth_key)) {
					$time0 = FrameworkManager::getTime();
					$package = Page::get('package');
					
					if (empty($package)) return $this->getErrorXml('Missing required package');
					
					try {
						$package_results = CWI_MANAGER_SyncManager::syncModelPackage($package); // Collection
					} catch (Exception $e) {
						return $this->getErrorXml('Unable to sync package ' . $package . ' because ' . $e->getMessage());
					}
					$output = '<response><valid>true</valid><package>';

					while ($result = $package_results->getNext()) {
						$output .= '<modelUpdate><model>' . $result->getModel()->getName() . '</model><type>' . $result->getType() . '</type></modelUpdate>';
					}
					$time1 = FrameworkManager::getTime();
					$output .= '</package><time>' . ($time1-$time0) . '</time></response>';
					
					return $output;
				}
				break;
			case 'syncmodel':
				if ($this->verifyAuthKey($auth_key)) {
					$model = Page::get('model');
					
					if (empty($model)) return $this->getErrorXml('Missing required model');
					
					try {
						$model_result = CWI_MANAGER_SyncManager::syncModel($model); // CWI_DB_ModelResult
					} catch (Exception $e) {
						return $this->getErrorXml('Unable to sync model ' . $model . ' because ' . $e->getMessage());
					}
					
					return '<response><valid>true</valid><modelUpdate><model>' . $model . '</model><type>' . $model_result->getType() . '</type></modelUpdate></response>';
				} else return $this->getInvalidAuthorizationKeyXml();
				break;
			default:
				$message = 'Unsupported action';
				if (!empty($action)) $message .= ': ' . $action;
				return $this->getErrorXml($message);
				break;
		}
		
		return 'CMSUPDATE';
	}
}

?>