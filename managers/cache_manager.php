<?php

class CWI_PROVIDER_ClassFileHolder {
	private $name, $classFile, $className, $config;
	
	public function __construct($name, $class_file, $class_name) {
		$this->name = $name;
		$this->classFile = $class_file;
		$this->className = $class_name;
		$this->config = new Dictionary();
	}
	public function getName() { return $this->name; }
	public function getClassFile() { return $this->classFile; }
	public function getClassName() { return $this->className; }
	public function getConfig() { return $this->config; }
	public function createInstance() {
		$class_name = $this->getClassName();
		if (!class_exists($class_name)) {
			include_once($this->getClassFile());
		}
		$provider = new $class_name;
		$provider->init($this->getName(), $this->getConfig());
		return $provider;
	}
}
class CWI_MANAGER_CacheManager {
	private $initialized=false;
	private $cacheEnabled=null;
	private $cacheProviders;
	public static function getInstance() {
		$manager = Singleton::getInstance('CWI_MANAGER_CacheManager');
		return $manager;
	}
	
	private static function initialize() {
		$_this = CWI_MANAGER_CacheManager::getInstance();
		if (ConfigurationManager::get('ENABLE_CACHE') != 'true') return false;
		if (!$_this->initialized) {
			$_this->initialized = true;
			$_this->cacheProviders = new ProviderDictionary();
			if ($config = ConfigurationManager::getConfig()) {
				
				if ($cache_providers = $config->getPath('cacheManager/providers/add')) {
					foreach($cache_providers as $cache_provider) {
						if ($class_file = PathManager::translate($cache_provider->getParam('classFile'))) {
							$provider = new CWI_PROVIDER_ClassFileHolder($cache_provider->getParam('name'), $class_file, $cache_provider->getParam('className'));
							$_this->cacheProviders->set($cache_provider->getParam('name'), $provider);
						}
					}
				}
			}			
		}
		return true;
	}
	
	public static function isCacheEnabled() {

		$_this = CWI_MANAGER_CacheManager::getInstance();

		/**
		 * If the $cacheEnabled has already been determined, use the stored result (unless the site has not been fully initialized yet
		 */ 

		if (!is_null($_this->cacheEnabled) && FrameworkManager::isSiteInitialized()) return $_this->cacheEnabled;
		
		/**
		 * Otherwise, look for reasons to say NO
		 */
		$is_cache_enabled = true;
		
		$cache_path = ConfigurationManager::get('DIR_FS_CACHE');
		if (empty($cache_path)) {
			Custodian::log('CacheManager', 'DIR_FS_CACHE is empty');
			$is_cache_enabled = false;
		} else {
			$d = new ConfigDictionary();
			$d->set('cache_path', $cache_path);
			
			if (!file_exists($cache_path)) {
				Custodian::log('CacheManager', 'Cache path does not exist: ${cache_path}', $d);
				$is_cache_enabled = false;
			} else if (!is_writable($cache_path)) {
				Custodian::log('CacheManager', 'Cache path is not writable: ${cache_path}', $d);
				$is_cache_enabled = false;
			}
		}
		
		if (ConfigurationManager::get('ENABLE_CACHE') != 'true') $is_cache_enabled = false;

		// Store results so that we do not have to process this again
		$_this->cacheEnabled = $is_cache_enabled;

		// Return results
		return $is_cache_enabled;
	}
	public static function getProviders() {
		if (CWI_MANAGER_CacheManager::initialize()) {
			$_this = CWI_MANAGER_CacheManager::getInstance();
			return $_this->cacheProviders;
		}
	}
	public static function getProvider($name) {
		if (CWI_MANAGER_CacheManager::initialize()) {
			$_this = CWI_MANAGER_CacheManager::getInstance();
			
			if ($provider = $_this->cacheProviders->get($name)) {
				return $provider->createInstance();
			} else return false;
		}
	}
}

?>