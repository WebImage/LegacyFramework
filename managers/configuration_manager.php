<?php

use WebImage\Config\Config;

/**
 * 06/10/2009	(Robert Jones) Added configuration support for pages->location for authorization
 * 01/27/2010	(Robert Jones) Modified class to take advantage of the fact that CWI_XML_Compile::compile() now throws errors
 * 08/03/2010	(Robert Jones) Modified settings to include support for group
 * 08/22/2010	(Robert Jones) Added settings to included additional features for locking config values (ConfigurationSettingValue) so that they cannot be changed
 */
class ConfigurationSettingValue {
	private $locked = true;
	private $value;
	function __construct($value, $locked=true) {
		$this->value = $value;
		$this->locked = $locked;
	}
	public function isLocked() { return $this->locked; }
	public function getValue() { return $this->value; }
}
class ConfigurationManager {
	var $settings;
	var $_requestHandlers = array();
	var $_pathMappings = array(); // Used for mapping incoming URL DEPRECATE
	var $_pageSecureConnections = array();
	var $_locationRoles = array();
	private $config; // DEPRECATE

	public static function reset() {
		$_this = Singleton::getInstance('ConfigurationManager');
		$_this->settings = new Dictionary();
		$_this->_requestHandlers = array();
		$_this->_pathMappings = array(); // Used for mapping incoming URLs DEPRECATE
		$_this->_pageSecureConnections = array();
		$_this->_locationRoles = array();
	}
	
	public static function getInstance() {
		$_this = Singleton::getInstance('ConfigurationManager');
		if (is_null($_this->settings)) {
			#$_this->settings = new Dictionary();
			$_this->reset();
		}
		return $_this;
	}

	public static function getConfig() {
		return ConfigurationManager::getInstance()->config;
	}
	public static function setConfig(Config $config) {
		ConfigurationManager::getInstance()->config = $config;
	}

	/**
	 * Parse out configuration variables, i.e. %DIR_WS_HOME%
	 * @access static
	 * @return string
	 */
	public static function getValueFromString($string) {
		if (is_string($string)) {

			if (preg_match_all('/%(.+?)%/', $string, $matches)) {

				if (isset($matches[1])) {

					foreach($matches[1] as $match) {

						$get_value = $match;

						if ($value = ConfigurationManager::get($get_value)) {

							$string = str_replace('%'.$get_value.'%', $value, $string);

						}

					}
				}
			}
		}
		return $string;
	}

	private static function getRaw($name, $group) {
		$_this = ConfigurationManager::getInstance();//Singleton::getInstance('ConfigurationManager');

		if (!$group_settings = $_this->settings->get($group)) return false;
		
		if ($var_value_obj = $group_settings->get($name)) {

			return $var_value_obj;
			
		} else {
			return false;
		}

	}
	
	public static function get($name, $group='general') {
		if (empty($group)) $group = 'general';
		$_this = ConfigurationManager::getInstance();//Singleton::getInstance('ConfigurationManager');

		if ($var_value_obj = ConfigurationManager::getRaw($name, $group)) {

			$var_value = $var_value_obj->getValue();

			if (is_callable($var_value)) {
				$var_value = call_user_func($var_value, sprintf('%s.%s', $group, $name));
			}
			
			return ConfigurationManager::getValueFromString($var_value);
			
		} else return false;

	}

	/**
	 * @return ConfigurationSettingValue|bool Returns a ConfigurationSettingValue if this is a valid set request, otherwize it returns false
	 **/
	public static function set($name, $value, $group='general', $locked=false) {
		$_this = ConfigurationManager::getInstance();//Singleton::getInstance('ConfigurationManager');
		if (empty($group)) $group = 'general';
		if (!$_this->settings->get($group)) $_this->settings->set($group, new Dictionary());
		
		// Check if this value is actually writable
		if ($existing = ConfigurationManager::getRaw($name, $group)) {
			if ($existing->isLocked()) return false;
		}
		$new_config_value = new ConfigurationSettingValue($value, $locked);
		$_this->settings->get($group)->set($name, $new_config_value);
		return $new_config_value;
	}
	
	public static function setAndPersist($name, $value, $group='general', $locked=false) {
		
		#$_this = ConfigurationManager::getInstance();//Singleton::getInstance('ConfigurationManager');
		#if (empty($group)) $group = 'general';
		
		if (ConfigurationManager::set($name, $value, $group, $locked)) {
			FrameworkManager::loadLogic('configvalue');
			ConfigValueLogic::setGroupConfigValue($group, $name, $value, $locked);
		}
	}

	/**
	 * Convert an old XML based configuration to the new array format
	 */
	public static function convertConfigXmlToArray($xml_config_obj) {

		$config = array(
			'settings' => array(),
			'pages' => array(),
			'membership' => array(),
			'roleManager' => array(),
			'profile' => array(),
			'cacheManager' => array(),
			'store' => array(),
			'search' => array()
		);
	
		$_this = ConfigurationManager::getInstance();//Singleton::getInstance('ConfigurationManager');

		if (is_object($xml_config_obj) && is_a($xml_config_obj, 'CWI_XML_Traversal')) {
			/*
			 * Add settings
			*/
			if ($config_settings = $xml_config_obj->getPathSingle('settings')) {
	
				if ($set_vars = $config_settings->getPath('var')) {
	
					foreach($set_vars as $set_var) {
	
						$var_name	= $set_var->getParam('name');
						$var_value	= $set_var->getParam('value');
						$var_group	= ($set_var->getParam('group')) ? $set_var->getParam('group') : 'general';
						$var_locked	= ($set_var->getParam('locked') == 'true');
####################################$_this->set($var_name, $var_value, $var_group, $var_locked);
	
						if (!isset($config['settings'][$var_group])) $config['settings'][$var_group] = array();
						$config['settings'][$var_group][$var_name] = $var_value;
					}
	
				}
	
			}

			if ($page_settings = $xml_config_obj->getPathSingle('pages')) {

				/*
					<location path="REGEX_PATH">
				<authentication>
				<allow roles="CommaSeparatedRoles" />
				</authentication>
				</location>
				*/

				if ($locations = $page_settings->getPath('location')) {
						
					$config['pages']['locations'] = array();
						
					foreach($locations as $location) {
	
						$location_path = $location->getParam('path');
	
						/**
						 * 'location' => array(
						 array(
						 'path' => '/admin/.*',
						 'theme' => 'athenacms'
						 ),
						 array(
						 'path' => '/admin/content/.*',
						 'theme' => 'athenacms'
						 )
						 )
	
						*/
						$authorization = array(
							'allow' => array(
								'roles' => array()
							)
						);
	
						if ($xml_authorization = $location->getPathSingle('authorization')) {
	
							if ($xml_allow_roles = $xml_authorization->getPath('allow')) {
	
								foreach($xml_allow_roles as $xml_allow_role) {
	
									if ($allowed_roles = $xml_allow_role->getParam('roles')) {
	
										$param_roles = explode(',', $allowed_roles);
	
										foreach($param_roles as $param_role) {
												
											$authorization['allow']['roles'][] = trim($param_role);
												
										}
	
									}
	
								}
	
							}
	
						}
	
						$config['pages']['locations'][] = array(
							'path' => $location_path,
							'authorization' => $authorization
						);
	
					}
						
				}

				if ($request_handlers = $page_settings->getPath('requestHandlers/add')) {
	
					$config['pages']['requestHandlers'] = array();

					for ($i=0; $i < count($request_handlers); $i++) {

						$config['pages']['requestHandlers'][$request_handlers[$i]->getParam('name')] = array(
							'className' => $request_handlers[$i]->getParam('className'),
							'classFile' => $request_handlers[$i]->getParam('classFile'),
							'sortorder' => $request_handlers[$i]->getParam('sortorder')
						);
###########################################$_this->addRequestHandler($request_handlers[$i]->getParam('name'), $request_handlers[$i]->getParam('className'), $path, $sortorder);

					}
						
				}

				if ($path_mappings = $page_settings->getPath('pathMappings/add')) {

					$config['pages']['pathMappings'] = array();

					foreach($path_mappings as $path_mapping) {

						$path = $path_mapping->getParam('path');
						$translate = $path_mapping->getParam('translate');
						$request_handler = $path_mapping->getParam('requestHandler');

						$config['pages']['pathMappings'][] = array(
							'path' => $path ? $path : null,
							'translate' => $translate ? $translate : null,
							'requestHandler' => $request_handler ? $request_handler : null
						);

					}

				}

				if ($xml_page_secure_connections = $page_settings->getPath('requireSecureConnection/add')) {
	
					$config['pages']['requireSecureConnection'] = array();
						
					foreach($xml_page_secure_connections as $xml_path_regex) {
	
						$config['pages']['requireSecureConnection'][] = array(
							'pathRegex' => $xml_path_regex->getParam('path')
						);
	
####################################$_this->addPageSecureConnection($path->getParam('path'));
	
					}
				}

			}
	
			// Retrieve Database Settings
			if ($config_database = $xml_config_obj->getPathSingle('database')) {

				$config['database'] = array();

				if ($connections = $config_database->getPath('databaseConnections/add')) {

					$config['database']['connections'] = array();

					// Add each database connection
					foreach($connections as $connection) {
						$key_name	= $connection->getParam('name');
						$server	= $connection->getParam('server');
						$username	= $connection->getParam('username');
						$password	= $connection->getParam('password');
						$database	= $connection->getParam('database');
			
####################################ConnectionManager::addConnection($key_name, new DatabaseSetting($server, $username, $password, $database));
						$config['database']['connections'][$key_name] = array(
							'host' => $server,
							'username' => $username,
							'password' => $password,
							'database' => $database
							);
		
					}
	
				}

				if ($xml_table_sections = $xml_config_obj->getPath('database/tables')) {

					foreach($xml_table_sections as $xml_table_section) {

						if ($prefix = $xml_table_section->getParam('prefix')) {

							if (!isset($config['database']['tableSettings'])) $config['database']['tableSettings'] = array();
							$config['database']['tableSettings']['prefix'] = $prefix;

						}

					}
				}

				if ($tables = $config_database->getPath('tables/add')) {

					$config['database']['tables'] = array();

					foreach($tables as $table) {
						$name = $table->getParam('name');
						$value = $table->getParam('value');
						$use_global_prefix = $table->getParam('useGlobalPrefix');

						$config['database']['tables'][$name] = array(
							'key' => $value
						);
						if ($use_global_prefix) {
							$config['database']['tables'][$name]['useGlobalPrefix'] = !(strtolower($use_global_prefix) == 'false');
						}
					}

				}
	
			}

			// Membership
			if ($config_membership = $xml_config_obj->getPathSingle('membership')) {

				$config['membership'] = array(
					'providers' => array()
				);

				if ($default_provider = $config_membership->getParam('defaultProvider')) {
					$config['membership']['defaultProvider'] = $default_provider;
				}
	
				if ($xml_membership_providers = $config_membership->getPath('providers/add')) {

					// Add Membership Providers to Application
					foreach($xml_membership_providers as $xml_provider) {

						if ($class_file_path = $xml_provider->getParam('classFile')) {
								
							#if ($class_file_path = PathManager::translate($class_file_param)) {

							$provider_name = $xml_provider->getParam('name');

							$config['membership']['providers'][$provider_name] = array(
								'classFile' => $class_file_path,
							);

							foreach($xml_provider->getParams() as $param_key=>$param_value) {
								if ($param_key != 'name') $config['membership']['providers'][$provider_name][$param_key] = $param_value;
							}
################################################Membership::addProvider($provider_config);
							#}
						}
					}
				}
			}

			// Role Manager
			if ($xml_config_roles = $xml_config_obj->getPathSingle('roleManager')) {
		
				$config['roleManager'] = array(
					'defaultProvider' => null,
					'providers' => array()
					);
		
				if ($xml_default_provider = $xml_config_roles->getParam('defaultProvider')) {
					$config['roleManager']['defaultProvider'] = $xml_default_provider;
				}
		
				if ($xml_role_providers = $xml_config_roles->getPath('providers/add')) {
			
				// 	Add Role Providers to Application
					foreach($xml_role_providers as $xml_provider) {
			
						$provider_name = $xml_provider->getParam('name');
				
						$config['roleManager']['providers'][$provider_name] = array(
							'classFile' => $xml_provider->getParam('classFile')
						);
			
						foreach($xml_provider->getParams() as $param_key=>$param_value) {
							if ($param_key != 'name') $config['roleManager']['providers'][$provider_name][$param_key] = $param_value;
						}
	
####################################include_once($provider->getParam('classFile'));
	
####################################Roles::addProvider($provider_config);
					}
				}
			}
	
			// Profiles
			if ($xml_config_profiles = $xml_config_obj->getPathSingle('profile')) {

				$config['profile'] = array(
					'defaultProvider' => null,
					'providers' => array()
				);
	
				if ($default_provider = $xml_config_profiles->getParam('defaultProvider')) {
					$config['profile']['defaultProvile'] = $default_provider;
##############################Profiles::setDefaultProvider($default_provider);
				}
	
				if ($xml_profile_providers = $xml_config_profiles->getPath('providers/add')) {
		
					// Add Profile Profiles to Application
					foreach($xml_profile_providers as $xml_provider) {
		
						$provider_name = $xml_provider->getParam('name');
		
						$config['profile']['providers'][$provider_name] = array(
							'classFile' => $xml_provider->getParam('classFile')
						);
		
						foreach($xml_provider->getParams() as $param_key=>$param_value) {
									
							if ($param_key != 'name') $config['profile']['providers'][$provider_name][$param_key] = $param_value;
##########################################$provider_config->set($param_key, $param_value);
						}

####################################Profiles::addProvider($provider_config);
					}
				}
			}

			// Service Manager
			if ($xml_service_managers = $xml_config_obj->getPath('serviceManager')) {

				$config['serviceManager'] = array();

				foreach($xml_service_managers as $xml_service_manager) {

					$xml_service_types = $xml_service_manager->getChildren();

					foreach($xml_service_types as $xml_service_type) {

						if (is_a($xml_service_type, 'CWI_XML_Traversal')) {

							$type = $xml_service_type->getTagName();

							if (!isset($config['serviceManager'][$type])) $config['serviceManager'][$type] = array();

							if ($xml_services = $xml_service_type->getPath('add')) {

								foreach($xml_services as $xml_service) {

									$name = $xml_service->getParam('name');
									$value = $xml_service->getParam('value');
									#$xml_service_type->getParams();
									$config['serviceManager'][$type][$name] = $value;

								}

							}

						}

					}
				}

			}

			return $config;
		} else {
			return false;
		}	
	}

	/**
	 * Called by FrameworkManager::init() in order to keep some backwards compatability with "provider" classes that still call Configuration::xyz() methods directly
	 */
	public static function legacyInitConfig() {

		$_this = ConfigurationManager::getInstance();//Singleton::getInstance('ConfigurationManager');
		$config = self::getConfig();

		self::legacyInitConfigSettings($_this, $config);
		self::legacyInitConfigPageSettings($_this, $config);
		self::legacyInitConfigMembership($_this, $config);
		self::legacyInitConfigRoles($_this, $config);
	}
	/**
	 * Add settings
	 */
	private static function legacyInitConfigSettings($_this, $config) {

		$config_settings = isset($config['settings']) ? $config['settings'] : array();

		$var_locked = false;

		foreach ($config_settings as $var_group => $group_vars) {

			foreach ($group_vars as $var_name => $var_value) {

				$_this->set($var_name, $var_value, $var_group, $var_locked);

			}

		}
	}

	/**
	 * @param ConfigurationManager $_this
	 * @param $config
	 */
	private static function legacyInitConfigPageSettings($_this, $config) {
		/**
		 * Add page settings
		 */
		$config_pages = (isset($config['pages'])) ? $config['pages'] : array();
		$config_page_locations = (isset($config_pages['locations'])) ? $config_pages['locations'] : array();
		$config_path_mappings = (isset($config_pages['pathMappings'])) ? $config_pages['pathMappings'] : array();
		$config_request_handlers = (isset($config_pages['requestHandlers'])) ? $config_pages['requestHandlers'] : array();
		$config_secure_pages = (isset($config_pages['requireSecureConnection'])) ? $config_pages['requireSecureConnection'] : array();
		/**
		 * Add paths requiring authorization
		 */
		foreach ($config_page_locations as $location) {

			$location_path = $location['path'];
			$roles = (isset($location['authorization']['allow']['roles'])) ? $location['authorization']['allow']['roles'] : array();

			if (isset($location['authorization']['allow']['roles'])) {

				$_this->addLocationRoles($location_path, $roles);

			}

		}
		/**
		 * Add path mappings
		 */
		foreach ($config_path_mappings as $mapping) {

			$_this->addPathMapping(
				$mapping['path'],
				(isset($mapping['translate'])) ? $mapping['translate'] : '',
				(isset($mapping['requestHandler'])) ? $mapping['requestHandler'] : null
			);

		}

		/**
		 * Add request handlers
		 */
		$highest_sortorder = 100; // Keeps track of the request handler's highest sort order so that if "sortorder" is not specified it can be generated using the previously highest sort order - may need to be made a static member to keep track of this value globally

		foreach ($config_request_handlers as $handler_name => $request_handler) {

			$sortorder = (isset($request_handler['sortorder'])) ? $request_handler['sortorder'] : null;

			if ($path = PathManager::translate($request_handler['classFile'])) {

				if (is_numeric($sortorder)) {

					#CAUSING ERRORS: if ($sortorder > $highest_sortorder) $highest_sortorder = $sortorder;

				} else {

					$highest_sortorder++;
					$sortorder = $highest_sortorder;
				}

				$_this->addRequestHandler($handler_name, $request_handler['className'], $path, $sortorder);

			}

		}

		/**
		 * Pages requiring secure connections
		 */
		foreach ($config_secure_pages as $path) {

			$_this->addPageSecureConnection($path['path']);

		}
	}

	// Membership
	private static function legacyInitConfigMembership($_this, $config) {

		$config_membership = (isset($config['membership'])) ? $config['membership'] : array();

		$default_provider = (isset($config_membership['defaultProvider'])) ? $config_membership['defaultProvider'] : null;
		Membership::setDefaultProvider($default_provider);

		$providers = (isset($config_membership['providers'])) ? $config_membership['providers'] : array();

		foreach ($providers as $name => $info) {

			if ($class_file_path = PathManager::translate($info['classFile'])) {

				$info['classFile'] = $class_file_path;
				$provider_config = new ProviderConfiguration(array(
					'name' => $name
				));
				$provider_config->set('initialized', false);

				foreach ($info as $param_key => $param_value) {
					$provider_config->set($param_key, $param_value);
				}

				Membership::addProvider($provider_config);
			}

		}
	}

	// Role Manager
	private static function legacyInitConfigRoles($_this, $config) {

		$config_roles = (isset($config['roleManager'])) ? $config['roleManager'] : array();
		$providers = (isset($config_roles['providers'])) ? $config_roles['providers'] : array();

		$default_provider = (isset($config_roles['defaultProvider'])) ? $config_roles['defaultProvider'] : null;

		// Set default provider
		if (null !== $default_provider) Roles::setDefaultProvider($default_provider);

		// Add Role Providers to Application
		foreach ($providers as $name => $info) {

			#$provider->setParam('classFile', PathManager::translate($provider->getParam('classFile')));
			$info['classFile'] = PathManager::translate($info['classFile']);

			$provider_config = new Dictionary(array(
				'name' => $name
			));
			foreach ($info as $param_key => $param_value) {
				$provider_config->set($param_key, $param_value);
			}

			include_once($info['classFile']);

			Roles::addProvider($provider_config);
		}
	}

	/**
	 * NO LONGER USED Add a configuration object to the site
	 * @param array|CWI_XML_Traversal An array of settings
	 * @deprecated
	 */
	public static function addConfigSettings($config) {

		throw new Exception(sprintf('The method %s is now deprecated', __METHOD__));

	}

	/**
	 * Load a legacy xml file as an XML object
	 * @param string $config_xml_filepath
	 * @return bool|CWI_XML_Traversal|null
	 */
	public static function getConfigFileXml($config_xml_filepath) {

		if (file_exists($config_xml_filepath)) {

			FrameworkManager::loadLibrary('xml.compile');

			$xml_config = file_get_contents($config_xml_filepath);
			if (strlen($xml_config) == 0) return false;

			$xml_config_obj = null;

			try {
				$xml_config_obj = CWI_XML_Compile::compile($xml_config);
			} catch (CWI_XML_CompileException $e) {
				$xml_config_obj = null;
			}

			return $xml_config_obj;
		}

	}
	/**
	 * Adds a configuration file to the site 
	 * @param string $config_xml_filepath The full file to the configuration file - usually ending in config.xml
	 * @param string $xml_file_tag A xml_file_tag is an abbreviated way to identify a config file (<config><configFile file="/path/to/global_config.xml" tag="global" /></config>.  Additionally, the tag is also used for caching
	 * @return CWI_XML_Traversal|null
	 */
	public static function addConfigFile($config_xml_filepath, $xml_file_tag='NA') {
		$_this = ConfigurationManager::getInstance();//Singleton::getInstance('ConfigurationManager');

		$xml_config_obj = self::getConfigFileXml($config_xml_filepath);

		if (null !== $xml_config_obj) {
			/**
			 * Rename this files root tag from <config> to <configFile> so that as a <configFile> it can be added as a child to the global <config>, so
			 * <config />
			 * ... becomes ...
			 * <config>
			 *	<configFile file="$config_xml_filepath" tag="$tag" />
			 *	<configFile file="$config_xml_filepath" tag="$tag" />
			 *	...
			 * </config>
			 */
			return ConfigurationManager::addConfigSettings($xml_config_obj);

		} else { // File not found

			return false;

		}
	}

	
	/** 
	 *
	 *
	 * ALL FUNCTIONS BELOW HERE NEED SOME OTHER SCHEMA TO GET THE DATA IN - THIS IS SLOPPY FOR NOW
	 *
	 *
	 */
	// DEPRECATE
	public static function addPathMapping($path_regex, $translation, $handler=null) {
		$_this = ConfigurationManager::getInstance();//Singleton::getInstance('ConfigurationManager');
		$_this->_pathMappings[] = array(
			'path_regex' => $path_regex,
			'translation' => $translation,
			'handler' => $handler
		);
	}

	public static function getPathMappings() {
		$_this = ConfigurationManager::getInstance();//Singleton::getInstance('ConfigurationManager');
		return $_this->_pathMappings;
	}

	/**
	 * @param int $sortorder The preference given to the specified request handlers (lower numbers float to the top and are used first)
	 **/
	public static function addRequestHandler($name, $class, $file, $sortorder=100) {
		$_this = ConfigurationManager::getInstance();//Singleton::getInstance('ConfigurationManager');
		array_unshift($_this->_requestHandlers, array(
			'name'	=> $name,
			'class'	=> $class,
			'file'	=> $file,
			'sortorder' => $sortorder
		));
	}

	public static function addPageSecureConnection($path_regex) {
		$_this = ConfigurationManager::getInstance();//Singleton::getInstance('ConfigurationManager');
		$_this->_pathSecureConnections[] = array(
			'path_regex' => $path_regex
		);
	}

	private function sortHandlers($handler_a, $handler_b) {
		if ($handler_a['sortorder'] < $handler_b['sortorder']) return -1;
		if ($handler_a['sortorder'] > $handler_b['sortorder']) return 1;
		else return 0;
	}
	
	public static function getRequestHandlers() {
		$_this = ConfigurationManager::getInstance();//Singleton::getInstance('ConfigurationManager');
		usort($_this->_requestHandlers, array($_this, 'sortHandlers'));
		return $_this->_requestHandlers;
	}

	public static function getRequestHandlerByName($name) {
		$_this = ConfigurationManager::getInstance();//Singleton::getInstance('ConfigurationManager');
		$request_handlers = $_this->_requestHandlers;
		foreach($request_handlers as $request_handler) {
			if ($request_handler['name'] == $name) return $request_handler;
		}
		return false;
	}

	public static function getPageSecureConnections() {
		$_this = ConfigurationManager::getInstance();//Singleton::getInstance('ConfigurationManager');
		return $_this->_pageSecureConnections;
	}

	public static function addLocationRoles($path_regex, $roles_array) {
		$_this = ConfigurationManager::getInstance();//Singleton::getInstance('ConfigurationManager');
		$_this->_locationRoles[] = array(
			'path_regex' => $path_regex,
			'roles' => $roles_array
		);
	}

	public static function getLocationRoles() {
		$_this = ConfigurationManager::getInstance();//Singleton::getInstance('ConfigurationManager');
		return $_this->_locationRoles;
	}

	/**
	 * TODO: Finish transitioning to array based config
	 */
	public static function addConfigSettingsFromDb(Config $config) {

		$_this = ConfigurationManager::getInstance();//Singleton::getInstance('ConfigurationManager');

		FrameworkManager::loadLogic('configvalue');
		$values = ConfigValueLogic::getConfigValues();

		while ($value = $values->getNext()) {

			$group = (empty($value->group_key)) ? 'general' : $value->group_key;
			if (!isset($config['settings'][$group])) $config['settings'][$group] = new Config();

			$config['settings'][$group][$value->field] = $value->value;

		}

		$locked = false;
		foreach($config['settings'] as $group => $settings) {

			foreach($settings as $name => $value) {

				$_this->set($name, $value, $group, $locked);

			}

		}

		return $config;

	}

}
// Alias for ConfigurationManager
if (function_exists('class_alias')) class_alias('ConfigurationManager', 'CM');
else {
	class CM extends ConfigurationManager {}
}
?>