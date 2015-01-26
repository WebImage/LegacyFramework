<?php

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
	var $_pathMappings = array(); // Used for mapping incoming URLs
	var $_pageSecureConnections = array();
	var $_locationRoles = array();
	private $configRoot;

	public static function reset() {
		$_this = Singleton::getInstance('ConfigurationManager');
		$_this->settings = new Dictionary();
		$_this->_requestHandlers = array();
		$_this->_pathMappings = array(); // Used for mapping incoming URLs
		$_this->_pageSecureConnections = array();
		$_this->_locationRoles = array();
		$_this->configRoot = null;

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
		#$_this = Singleton::getInstance('ConfigurationManager');
		#return $_this->configRoot;
		return ConfigurationManager::getInstance()->configRoot;
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
	 * Add a configuration object to the site
	 */
	public static function addConfigSettings($xml_config_obj) {
		
		$_this = ConfigurationManager::getInstance();//Singleton::getInstance('ConfigurationManager');
		
		if (is_object($xml_config_obj) && is_a($xml_config_obj, 'CWI_XML_Traversal')) {

			/** 
			 * Add new file onto existing config stack
			 */

			if (is_null($_this->configRoot)) {
				$_this->configRoot = new CWI_XML_Traversal('config');
			}

			$xml_config_obj_children = $xml_config_obj->getChildren();
			foreach($xml_config_obj_children as $xml_config_obj_child) {
				$_this->configRoot->addChild($xml_config_obj_child);
			}
			#$_this->configRoot->addChild($xml_config_obj);

			// Settings
			if ($config_settings = $xml_config_obj->getPathSingle('settings')) {

				if ($set_vars = $config_settings->getPath('var')) {

					foreach($set_vars as $set_var) {
						$var_name	= $set_var->getParam('name');
						$var_value	= $set_var->getParam('value');
						$var_group	= ($set_var->getParam('group')) ? $set_var->getParam('group') : 'general';
						$var_locked	= ($set_var->getParam('locked') == 'true');
						$_this->set($var_name, $var_value, $var_group, $var_locked);
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

					foreach($locations as $location) {

						$location_path = $location->getParam('path');

						if ($authorization = $location->getPathSingle('authorization')) {

							if ($allow_roles = $authorization->getPath('allow')) {

								$roles = array();

								foreach($allow_roles as $allow_role) {

									if ($allowed_roles = $allow_role->getParam('roles')) {

										$param_roles = explode(',', $allowed_roles);
										foreach($param_roles as $param_role) {
											array_push($roles, trim($param_role));
										}

									}

								}

								$_this->addLocationRoles($location_path, $roles);

							}

						}

					}
				}

				if ($page_mappings = $page_settings->getPath('pathMappings/add')) {

					foreach($page_mappings as $mapping) {

						$_this->addPathMapping( $mapping->getParam('path'), $mapping->getParam('translate'), $mapping->getParam('requestHandler') );

					}

				}

				$highest_sortorder = 100; // Keeps track of the request handler's highest sort order so that if "sortorder" is not specified it can be generated using the previously highest sort order - may need to be made a static member to keep track of this value globally
				
				if ($request_handlers = $page_settings->getPath('requestHandlers/add')) {

					for ($i=0; $i < count($request_handlers); $i++) {
						
						// Make sure path exists before including
						if ($path = PathManager::translate($request_handlers[$i]->getParam('classFile'))) {
							
							$sortorder = $request_handlers[$i]->getParam('sortorder');
							
							if (is_numeric($sortorder)) {
								
								if ($sortorder > $highest_sortorder) $highest_sortorder = $sortorder;
								
							} else {
								
								$sortorder = $highest_sortorder + 1;
								$highest_sortorder = $sortorder;
								
							}
							
							$_this->addRequestHandler($request_handlers[$i]->getParam('name'), $request_handlers[$i]->getParam('className'), $path, $sortorder);
						
						}	
					
					}
					
				}

				if ($page_secure_connections = $page_settings->getPath('requireSecureConnection/add')) {

					foreach($page_secure_connections as $path) {

						$_this->addPageSecureConnection($path->getParam('path'));

					}
				}

			}

			// Retrieve Database Settings
			if ($config_database = $xml_config_obj->getPathSingle('database')) {

				if ($connections = $config_database->getPath('databaseConnections/add')) {

					// Add each database connection
					foreach($connections as $connection) {
						$key_name	= $connection->getParam('name');
						$server		= $connection->getParam('server');
						$username	= $connection->getParam('username');
						$password	= $connection->getParam('password');
						$database	= $connection->getParam('database');

						ConnectionManager::addConnection($key_name, new DatabaseSetting($server, $username, $password, $database));
					}

				}

			}

			// Membership
			
			if ($config_membership = $xml_config_obj->getPathSingle('membership')) {

				if ($default_provider = $config_membership->getParam('defaultProvider')) {
					Membership::setDefaultProvider($default_provider);
				}

				if ($membership_providers = $config_membership->getPath('providers/add')) {

					// Add Membership Providers to Application
					foreach($membership_providers as $provider) {
						if ($class_file_param = $provider->getParam('classFile')) {
							
							if ($class_file_path = PathManager::translate($class_file_param)) {
								$provider->setParam('classFile', $class_file_path);

								$provider_config = new ProviderConfiguration();
								$provider_config->set('initialized', false);
								foreach($provider->getParams() as $param_key=>$param_value) {
									$provider_config->set($param_key, $param_value);
								}
								Membership::addProvider($provider_config);
							}
						}
					}
				}
			}

			// Role Manager
			if ($config_roles = $xml_config_obj->getPathSingle('roleManager')) {

				if ($default_provider = $config_roles->getParam('defaultProvider')) {
						Roles::setDefaultProvider($default_provider);
				}

				if ($role_providers = $config_roles->getPath('providers/add')) {

					// Add Role Providers to Application
					foreach($role_providers as $provider) {
						$provider->setParam('classFile', PathManager::translate($provider->getParam('classFile')));

						$provider_config = new Dictionary();
						foreach($provider->getParams() as $param_key=>$param_value) {
							$provider_config->set($param_key, $param_value);
						}
						include_once($provider->getParam('classFile'));

						Roles::addProvider($provider_config);
					}
				}
			}

			// Profiles
			if ($config_profiles = $xml_config_obj->getPathSingle('profile')) {
				if ($default_provider = $config_profiles->getParam('defaultProvider')) {
					Profiles::setDefaultProvider($default_provider);
				}

				if ($profile_providers = $config_profiles->getPath('providers/add')) {

					// Add Profile Profiles to Application
					foreach($profile_providers as $provider) {

						$provider->setParam('classFile', PathManager::translate($provider->getParam('classFile')));

						$provider_config = new Dictionary();
						foreach($provider->getParams() as $param_key=>$param_value) {
							$provider_config->set($param_key, $param_value);
						}
						if ($class_file = $provider->getParam('classFile')) {
							#if (@include_once($class_file)) {

								Profiles::addProvider($provider_config);
							#}
						}
					}
				}
			}

			return $xml_config_obj;
		} else {
			return false;
		}

	}
	/**
	 * Adds a configuration file to the site 
	 * @param string $config_xml_filepath The full file to the configuration file - usually ending in config.xml
	 * @param string $xml_file_tag A xml_file_tag is an abbreviated way to identify a config file (<config><configFile file="/path/to/global_config.xml" tag="global" /></config>.  Additionally, the tag is also used for caching 
	 */
	public static function addConfigFile($config_xml_filepath, $xml_file_tag='NA') {		
		$_this = ConfigurationManager::getInstance();//Singleton::getInstance('ConfigurationManager');
		
		$configuration_file = $config_xml_filepath;
		if (file_exists($configuration_file)) {
			
			$xml_config = file_get_contents($configuration_file);
			if (strlen($xml_config) == 0) return false;

			$valid_xml_object = true;

			FrameworkManager::loadLibrary('xml.compile');

			try {
				$xml_config_obj = CWI_XML_Compile::compile($xml_config);
			} catch (CWI_XML_CompileException $e) {
				$valid_xml_object = false;
			}

			if ($valid_xml_object && is_object($xml_config_obj) && is_a($xml_config_obj, 'CWI_XML_Traversal')) {
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
#				$xml_config_obj->setTagName('configFile');
#				$xml_config_obj->setParam('file', $config_xml_filepath);
#				$xml_config_obj->setParam('tag', $xml_file_tag);
#				echo '<hr><pre>';
#				echo $xml_config_obj->debug();
#				echo '</pre>';
				#return ConfigurationManager::addConfigSettings($xml_config_obj, $config_xml_filepath, $xml_file_tag);
				return ConfigurationManager::addConfigSettings($xml_config_obj);
			}

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
	public static function addPathMapping($path_regex, $translation, $handler) {
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
	
	public static function addConfigSettingsFromDb() {
		$_this = ConfigurationManager::getInstance();//Singleton::getInstance('ConfigurationManager');
		
		FrameworkManager::loadLogic('configvalue');
		$values = ConfigValueLogic::getConfigValues();
		
		while ($value = $values->getNext()) {
			$_this->set($value->field, $value->value, (empty($value->group_key) ? 'general' : $value->group_key), ($value->locked==1));
		}
	}

}
// Alias for ConfigurationManager
if (function_exists('class_alias')) class_alias('ConfigurationManager', 'CM');
else {
	class CM extends ConfigurationManager {}
}
?>