<?php

use WebImage\Application\ApplicationInterface;
use WebImage\Config\LegacyConfig;
use WebImage\Application\LegacyApplication;

/**
 * 01/27/2010	(Robert Jones) Added new second parameter "tag" to all instances of ConfigurationManager::addConfigFile()
 * 02/02/2010	(Robert Jones) Added isSiteInitialized() to determine whether the site/app is ready to run.  This was primarily added for the CWI_MANAGER_CacheManager so that it will not store certain initialization values permanently until the site is up
 * 04/26/2010	(Robert Jones) Added a return value for all load* methods so that the methods will return true of false, depending on whether or not the file was successfully loaded
 * 09/13/2010	(Robert Jones) Added support for running framework from command line
 * 09/18/2010	(Robert Jones) Added loadBaseLibrary, loadBaseManager, loadBaseLogic to speed up load times of libraries or managers known to only exist in the base
 * 09/08/2012	(Robert Jones) Modified framework class's cache path to use system tmp directory instead of trying to check if a writable directory has been created in the framework/sites/global directory (which inevitably would get forgotten each time a new copy of the framework was installed)
 * 10/04/2012	(Robert Jones) Removed $max_cache_age from base and global configuration files in favor of comparing cached file timestamps to configuration file timestamps
 */

define('LOGLEVEL_EMERGENCY', 'emerg'); // Emergencies - system is unusable
define('LOGLEVEL_ALERT', 'alert'); // Action required
define('LOGLEVEL_CRITICAL', 'crit');
define('LOGLEVEL_ERROR', 'error');
define('LOGLEVEL_WARNING', 'warn');
define('LOGLEVEL_NOTICE', 'notice');
define('LOGLEVEL_INFO', 'info');
define('LOGLEVEL_DEBUG', 'debug');

define('FRAMEWORK_MODE_WEB', 'website');
define('FRAMEWORK_MODE_CLI', 'cli');

if (!defined('DS')) define('DS', DIRECTORY_SEPARATOR);

class FrameworkManagerMarkTime {
	private $name, $time, $memory;
	function __construct($name, $time, $memory) {
		$this->name = $name;
		$this->time = $time;
		$this->memory = $memory;
	}
	public function getName() { return $this->name; }
	public function getTime() { return $this->time; }
	public function getMemory() { return $this->memory; }
}

class FrameworkManager {
	#var $_siteId;
	/**
	 * @var LegacyApplication $app (temporarily being stored here until we can do a full framework transition away from using FrameworkManager)
	 */
	private $app;
	private static $isSiteInitialized = false;

	private static $startTime;
	private static $markedTimes = array();

	/**
	 * Get Singleton
	 */
	public static function getInstance() {
		static $instances;
		if (!isset($instances[0])) {
			$instances[0] = new FrameworkManager();
		}
		return $instances[0];
	}
	public static function getStartTime() {
		return self::$startTime;
	}
	public static function markTime($name) {
		self::$markedTimes[] = new FrameworkManagerMarkTime($name, FrameworkManager::getTime(), memory_get_usage());
	}
	public static function renderTimes() {

		$output = '';

		/* Baseline, without loading any framework files, was about 332 KB */
		$output .= '<table border="1">';
		$output .= '<tr>';
		$output .= '<th>Name</th>';
		$output .= '<th>Time</th>';
		$output .= '<th>Memory</th>';
		$output .= '<th>Memory Increase</th>';
		$output .= '</tr>';

		$total_time = 0;

		for($i=0; $i < count(self::$markedTimes); $i++) {

			$previous = ($i > 0) ? self::$markedTimes[$i-1] : null;
			$current = self::$markedTimes[$i];

			$time_taken = is_null($previous) ? 'NA' : ($current->getTime() - $previous->getTime());
			$memory_increase = is_null($previous) ? 'NA' : ($current->getMemory() - $previous->getMemory());

			if (is_numeric($time_taken)) $total_time += $time_taken;

			if ($time_taken > 0.01) {
				$time_style = 'background-color:#c00;color:#fff;';
			} else if ($time_taken > 0.001) {
				$time_style = 'background-color:#ff0;';
			} else {
				$time_style = '';
			}

			$byte = 1;
			$kilobyte = 1024 * $byte;

			if ($memory_increase > 300*$kilobyte) {
				$memory_style = 'background-color:#c00;color:#fff';
			} else if ($memory_increase > 100*$kilobyte) {
				$memory_style = 'background-color:#ff0;';
			} else {
				$memory_style = '';
			}

			$output .= '<tr>';

			$output .= '<td>' . $current->getName() . '</td>';
			$output .= '<td align="right" style="' . $time_style . '">' . number_format(round($time_taken, 6), 6) . '</td>';
			$output .= '<td align="right" style="' . $memory_style . '">' . number_format($current->getMemory()/1024) . ' kb</td>';
			$output .= '<td align="right" style="' . $memory_style . '">' . number_format($memory_increase/1024) . ' kb</td>';

			$output .= '</tr>';

		}

		if (count(self::$markedTimes) > 1) {
			$output .= '<tr><td colspan="4"><strong>Total Time: ' . number_format(round($total_time, 6), 6) . '</td></tr>';
		}

		$output .= '</table>';
		return $output;
	}

	/**
	 * Get a file path that can be used within this class to cache/store framework related files
	 * @param $filename A simple string value that will be used to identify the tmp file
	 **/
	private static function getTmpFile($file_name) {
		/**
		 * Create a unique framework key by hashing __FILE__
		 * This unique key allows multiple copies of the framework to be hosted on the same server.
		 * Otherwise the cache files for the different copies of the framework would cross contaminate each other
		 **/
		$unique_framework_key = md5(__FILE__);
		// Concatenate the temp directory with the $key and a hashed
		return sys_get_temp_dir() . DIRECTORY_SEPARATOR . $file_name . '-' . $unique_framework_key;
	}
	/**
	 * Initialize framework.
	 *
	 * Can be called in one of two flavors: [NEW/PREFERRED] init(array $config_site, $mode, $domain='') -- OR -- [legacy] init($mode, $domain='')
	 * @param array $config_site An array with values to use for site configuration
	 * @param string $mode How the framework is being run - typically FRAMEWORK_MODE_WEB or FRAMEWORK_MODE_CLI (command line interface)
	 * @param string $domain The domain to use, by default this will be filled from the webs
	 * @param LegacyApplication $app The application instance to use
	 */
	public static function init($param1=null, $param2=null, $param3=null) {
		$mode = null;
		$config_site = null;
		$domain = '';
		$require_domain = false;
		if (is_array($param1)) { // init(array $config_site, $mode=FRAMEWORK_MODE_WEB, $domain='')
			$config_site = $param1;
			$mode = $param2;
			$domain = $param3;
		} else { // init($mode=FRAMEWOKR_MODE_WEB, $domain)
			$mode = $param1;
			$domain = $param2;
		}
		// Make sure $mode is set to a proper value
		if (empty($mode) || $mode != FRAMEWORK_MODE_CLI) $mode = FRAMEWORK_MODE_WEB;

		self::$startTime = FrameworkManager::getTime();
		$time0 = $_SERVER['REQUEST_TIME'];

		FrameworkManager::markTime(__class__ . '->init() begin usage');

		$start_time = FrameworkManager::getTime();

		// Base for all files
		$dir_fs_framework = dirname(dirname(dirname(__FILE__))) . DIRECTORY_SEPARATOR;

		// Framework Base
		$dir_fs_framework_base	= $dir_fs_framework . 'base' . DIRECTORY_SEPARATOR;
		$dir_fs_framework_sites = $dir_fs_framework . 'sites' . DIRECTORY_SEPARATOR;

		// Domain name - framework app files
		if (empty($domain)) {
			if (isset($_SERVER['SERVER_NAME']) && !empty($_SERVER['SERVER_NAME'])) {
				$domain = $_SERVER['SERVER_NAME'];
			} else if (isset($_SERVER['HTTP_HOST']) && !empty($_SERVER['HTTP_HOST'])) {
				$domain = $_SERVER['HTTP_HOST'];
			} else if ($mode == FRAMEWORK_MODE_WEB) die("We'll be right back!\n<!-- // Error: Domain Not Initialized // -->");
		}

		// Define framework directories
		if (!defined('DIR_FS_FRAMEWORK_BASE')) define('DIR_FS_FRAMEWORK_BASE',	$dir_fs_framework_base);

		FrameworkManager::markTime(__class__ . '->init() before library usage');
		// Load Path Manager
		include_once(DIR_FS_FRAMEWORK_BASE . 'managers' . DIRECTORY_SEPARATOR . 'path_manager.php');
		FrameworkManager::markTime(__class__ . '->init() after path manager usage');

		/**
		 * Load required libraries
		 */
		FrameworkManager::markTime(__class__ . '->init() before basics usage');
		// Basic functions/classes
		FrameworkManager::loadBaseLibrary('basics');

		/**
		 * Load main configuration files
		 */
		FrameworkManager::loadBaseManager('configuration');

		// Reset all singleton instances
		Singleton::reset();

		// Add bsae path
		PathManager::add(DIR_FS_FRAMEWORK_BASE);

		FrameworkManager::markTime(__class__ . '->init() after basics usage');

		FrameworkManager::loadBaseLibrary('controls');

		FrameworkManager::markTime(__class__ . '->init() after controls usage');

		FrameworkManager::loadBaseManager('connection');

		FrameworkManager::markTime(__class__ . '->init() after connection');

		FrameworkManager::loadBaseLibrary('compilers');

		FrameworkManager::markTime(__class__ . '->init() after compilers');

		FrameworkManager::loadBaseLibrary('database');

		FrameworkManager::markTime(__class__ . '->init() after database');

		FrameworkManager::loadBaseLibrary('pages');

		FrameworkManager::markTime(__class__ . '->init() after library usage');

		FrameworkManager::loadBaseLibrary('xml.xml');

		$config = new LegacyConfig([
			'settings' => [
				'general' => [
					'FRAMEWORK_MODE' => $mode,
					'DOMAIN' => $domain,
					'DIR_FS_FRAMEWORK' => $dir_fs_framework,
					'DIR_FS_FRAMEWORK_BASE' => DIR_FS_FRAMEWORK_BASE,
					'DIR_FS_FRAMEWORK_SITES' => $dir_fs_framework_sites,
					'DIR_FS_HOME' => $_SERVER['DOCUMENT_ROOT'] . DIRECTORY_SEPARATOR,
				]
			]
		]);

		$cache_config_base = self::getTmpFile('config_base.xml.cache');
		$cache_config_global = self::getTmpFile('config_global.xml.cache');

		$is_framework_caching_enabled = true;
		if (isset($_GET['nocache'])) $is_framework_caching_enabled = false; // Disable cache if "nocache" is in the query string

		$active_cached_configs = false;

FrameworkManager::markTime(__class__ . '->init() before is_framework_caching_enabled');

		$config_cache_explanation = array();

		$file_config_base = $config['settings']['general']['DIR_FS_FRAMEWORK_BASE'] . 'config' . DIRECTORY_SEPARATOR . 'config.php';
		$file_config_global = $config['settings']['general']['DIR_FS_FRAMEWORK_SITES'] . 'global' . DIRECTORY_SEPARATOR . 'config' . DIRECTORY_SEPARATOR . 'config.php';
		$file_config_global_alt = $config['settings']['general']['DIR_FS_FRAMEWORK_SITES'] . 'global' . DIRECTORY_SEPARATOR . 'config' . DIRECTORY_SEPARATOR . 'config.xml';

		if ($is_framework_caching_enabled) {
			try {
				$active_cached_configs = true;

				array_push($config_cache_explanation, 'Checking global');

				if ($active_cached_configs && file_exists($cache_config_global)) {

					array_push($config_cache_explanation, 'File exists: ' . $cache_config_global);

					if (file_exists($file_config_global_alt) && filemtime($cache_config_global) < filemtime($file_config_global_alt)) {

						array_push($config_cache_explanation, 'Global config cache needs refreshing');

						$active_cached_configs = false;

					} else {

						#array_push($config_cache_explanation, 'Last Update LT Max Cache Age');
						array_push($config_cache_explanation, 'Global config cache current');

						$config_global = unserialize(@file_get_contents($cache_config_global));

						if (is_object($config_global) && is_a($config_global, 'CWI_XML_Traversal')) { // Make sure object is valid
							// Valid
							array_push($config_cache_explanation, 'Valid config');

						} else {

							array_push($config_cache_explanation, 'Invalid config');
							$active_cached_configs = false;
						}
					}

				} else {

					array_push($config_cache_explanation, 'Global cache file does not exist: ' . $cache_config_global);
					$active_cached_configs = false;

				}

			} catch (Exception $e) {
				// Error
				$active_cached_configs = false;

			}
		}

FrameworkManager::markTime(__class__ . '->init() before configs (active_cache_configs=' . ($active_cached_configs ? 'Yes':'No') . ')');

		// Merge base config
		$config->merge(new LegacyConfig(require $file_config_base));

		$config_global = null;

		if ($active_cached_configs) {
			/**
			 * A cached copy of config_global would indicate that the global config file is in the old XML format, convert it to the array format here
			 */
			$config_global = new LegacyConfig(ConfigurationManager::convertConfigXmlToArray($config_global) );

		} else {

			if (file_exists($file_config_global)) {

				$config_global = new LegacyConfig( require($file_config_global) );

			} else if (file_exists($file_config_global_alt)) {

				$xml_global_config = ConfigurationManager::getConfigFileXml($file_config_global_alt);

				if (null !== $xml_global_config) {

					$config_global = new LegacyConfig(ConfigurationManager::convertConfigXmlToArray($xml_global_config) );

				}

			}
		}

		if (null !== $config_global) $config->merge($config_global);

		ConfigurationManager::setConfig($config);

FrameworkManager::markTime(__class__ . '->init() after configs');

		/**
		 * Only cache if caching is enabled and we are not using a currently valid cached version of the config (!$active_cached_configs)
		 */
		if ($is_framework_caching_enabled && !$active_cached_configs) {
			@file_put_contents($cache_config_global, serialize($config_global));
		}
		// Make SessionManager available by default
		FrameworkManager::loadBaseManager('session');

		// Initialize Message Manager
		FrameworkManager::loadBaseManager('message');
		MessageManager::init();

FrameworkManager::markTime(__class__ . '->init() before initializeSite usage');

		if (
			$mode == FRAMEWORK_MODE_WEB ||
			($mode == FRAMEWORK_MODE_CLI &&
				(!empty($domain) || null !== $config_site)
			)
		) {

			// Convert site config into an array
			if (null !== $config_site) {
				$config_site = new LegacyConfig($config_site);
				$config->merge($config_site);
			}
			#$config_site =
			FrameworkManager::initializeSite($config);

			#$config->merge($config_site);
			#ConfigurationManager::setConfig($config);
		}

FrameworkManager::markTime(__class__ . '->init() end usage');

		/**
		 * Initialize event manager and start attaching events
		 */
		FrameworkManager::loadLibrary('event.manager'); // Note: this could potentially be moved above initialize

		// Make config backwards compatible with old service providers
		ConfigurationManager::legacyInitConfig();

		$end_time = FrameworkManager::getTime();
		FrameworkManager::getInstance()->app = LegacyApplication::create($config);

		FrameworkManager::autoload();
	}

	/**
	 * Automatically load files in APP_DIR/config/autoload/
	 */
	protected static function autoload() {

		$app_dir = ConfigurationManager::get('DIR_FS_FRAMEWORK_APP');
		if (!empty($app_dir)) {
			$autoload_dir = $app_dir . 'config' . DIRECTORY_SEPARATOR . 'autoload' . DIRECTORY_SEPARATOR;
			if (file_exists($autoload_dir)) {
				$autoload = glob($autoload_dir . '*.php');
				foreach($autoload as $file) {
					include_once($file);
				}
			}
		}

	}
	public static function getApplication() {
		return FrameworkManager::getInstance()->app;
	}
	/**
	 * Builds the file base for loadManager, loadLibrary, loadData, loadStruct, loadLogic, etc.
	 *
	 * @param string current_base The prepended base, usually a system path, i.e. /var/home/.../file.php or ~/libraries/
	 * @param string key The text value that is insert into the middle of the path, i.e. ~/libraries/{$key}.  If the value includes a directory slash, e.g. "/" then this will be converted to a plugin
	 * @param string append The value to be appended to the end of the string
	 */
	public static function checkKeyForPaths($current_base, &$key) {
		// PLUGINS
		$plugin = '';
		$keys = explode('/', $key, 2);
		if (count($keys) > 1) {
			$current_base = '/plugins/' . $keys[0] . $current_base;
			$key = $keys[1];
		}
		// PATHS
		$paths = explode('.', $key);
		$key = $paths[count($paths)-1];
		array_pop($paths);
		if (count($paths) > 0) {
			$current_base .= implode('/', $paths) . '/';
		}

		return $current_base;
	}

	public static function checkForAdditionalPaths($current_base, &$key) {

	}

	/**
	 * Loads a plugin - not yet implemented
	 */
	public static function loadPlugin($plugin_name) {
		/**
		 * 1. Check whether plugin has already been loaded
		 * 2. If plugin has not already been loaded, load its config file
		 */
		$const_name = 'PLUGIN_' . $plugin_name;
		if (!defined($const_name)) {
			define($const_name, 'loaded');
			$file_name = '~/plugins/' . $plugin_name . '/config/config.xml';
			$valid_xml = true;
			try {
				$xml = CWI_XML_Compile::compile( file_get_contents( PathManager::translate($file_name) ) );
			} catch (CWI_XML_CompileException $e) {
				$valid_xml = false;
			}
			if ($valid_xml) {
				// Do something;
			}
		}

		return true;
	}
	public static function loadLibrary($library_name) { // Static function
		$file_base = FrameworkManager::checkKeyForPaths('/libraries/', $library_name);
		$file_name = '~' . $file_base . $library_name . '.php';

		if ($real_path = PathManager::translate($file_name)) {
			return include_once($real_path);
		} else {
			FrameworkManager::log(LOGLEVEL_WARNING, 'FrameworkManager::loadLibrary() failed to load: ' . $file_name);
			return false;
		}
	}
	public static function loadBaseLibrary($library_name) {
		$path = DIR_FS_FRAMEWORK_BASE . 'libraries' . DIRECTORY_SEPARATOR . str_replace('.', DIRECTORY_SEPARATOR, $library_name) . '.php';
		include_once($path);
	}
	public static function loadManager($manager_name) { // Static function
		$file_base = FrameworkManager::checkKeyForPaths('/managers/', $manager_name);
		$file_name = '~' . $file_base . $manager_name . '_manager.php';
		if ($real_path = PathManager::translate($file_name)) {
			return include_once($real_path);
		} else {
			FrameworkManager::log(LOGLEVEL_WARNING, 'FrameworkManager::loadManager() failed to load: ' . $file_name);
			return false;
		}
	}
	public static function loadBaseManager($manager_name) { // Static function
		$path = DIR_FS_FRAMEWORK_BASE . 'managers' . DIRECTORY_SEPARATOR . str_replace('.', DIRECTORY_SEPARATOR, $manager_name) . '_manager.php';
		include_once($path);
	}

	public static function loadLogic($logic_name) {
		$file_base = FrameworkManager::checkKeyForPaths('/logic/', $logic_name);
		$file_name = '~' . $file_base . $logic_name . '/' . $logic_name . '.php';
		if ($real_path = PathManager::translate($file_name)) {
			return include_once($real_path);
		} else {
			FrameworkManager::log(LOGLEVEL_WARNING, 'FrameworkManager::loadLogic() failed to load: ' . $file_name);
			return false;
		}
	}
	public static function loadBaseLogic($logic_name) {
		$file_base = FrameworkManager::checkKeyForPaths('/logic/', $logic_name);
		$path = substr(DIR_FS_FRAMEWORK_BASE, 0, -1) . $file_base . $logic_name . DIRECTORY_SEPARATOR . $logic_name . '.php';
		include_once($path);
	}
	public static function loadStruct($model_name) {
		$file_base = FrameworkManager::checkKeyForPaths('/data/', $model_name);
		$file_name = '~' . $file_base . $model_name . '/' . $model_name . '_structure.php';
		if ($real_path = PathManager::translate($file_name)) {
			include_once($real_path);
		} else {
			FrameworkManager::log(LOGLEVEL_WARNING, 'FrameworkManager::loadStruct() failed to load: ' . $file_name);
			return false;
		}
	}
	public static function loadDAO($dao_name) {
		$file_base = FrameworkManager::checkKeyForPaths('/data/', $dao_name);
		$file_name = '~' . $file_base . $dao_name . '/' . $dao_name . '_dao.php';
		if ($real_path = PathManager::translate($file_name)) {
			return include_once($real_path);
		} else {
			FrameworkManager::log(LOGLEVEL_WARNING, 'FrameworkManager::loadDAO() failed to load: ' . $file_name);
			return false;
		}

	}
	public static function loadControl($control_name) {
		$file_base = FrameworkManager::checkKeyForPaths('/controls/', $control_name);
		$file_name = '~' . $file_base . $control_name . '/' . $control_name . '.php';
		if ($real_path = PathManager::translate($file_name)) {
			include_once($real_path);
		} else {
			FrameworkManager::log(LOGLEVEL_WARNING, 'FrameworkManager::loadControl() failed to load: ' . $file_name);
			return false;
		}
	}

	public static function getTime() {
		$time = microtime();
		$time = explode(" ", $time);
		$time = $time[1] + $time[0];
		return $time;
	}

	public static function isSiteInitialized() { return self::$isSiteInitialized;	}

	/**
	 * Initialize a site
	 * @param LegacyConfig $config
	 * @return LegacyConfig $config
	 */
	private static function initializeSite(LegacyConfig $config) {

		FrameworkManager::markTime(__class__ . '->initializeSite() before loadSiteLogic');

		FrameworkManager::loadBaseLogic('site');

		$debug = array();
		$domain = $config['settings']['general']['DOMAIN'];
		$debug[] = 'Domain from settings: ' . $domain;

		if (substr($domain, 0, 4) == 'www.') $domain = substr($domain, 4);

		$dir_app = $config['settings']['general']['DIR_FS_FRAMEWORK_SITES'] . $config['settings']['general']['DOMAIN'] . DIRECTORY_SEPARATOR;

		FrameworkManager::markTime(__class__ . '->initializeSite() before DB domain check');

		$debug[] = '$dir_app: ' . $dir_app . '; Exists: ' . (file_exists($dir_app) ? 'Yes' : 'No') . PHP_EOL;

		if (file_exists($dir_app)) {

			$config['settings']['general']['SITE_ID'] = 0; // Legacy
			$config['settings']['general']['SITE_KEY'] = ''; // Legacy
			$config['settings']['general']['SITE_NAME'] = ''; // Legacy
			$config['settings']['general']['SITE_ENVIRONMENT'] = 'production'; // Generally production, staging, or development
			$config['settings']['general']['DIR_FS_FRAMEWORK_APP'] = $config['settings']['general']['DIR_FS_FRAMEWORK_SITES'] . $config['settings']['general']['DOMAIN'] . DIRECTORY_SEPARATOR;

			$valid_site = true;

		} else {

			$debug[] = 'ConnectionManager::hasConnection: ' . (ConnectionManager::hasConnection() ? 'Yes' : 'No');

			if (ConnectionManager::hasConnection() && $site = SiteLogic::getSiteByDomain($domain)) {

				$valid_site = true;

				// Site Values
				$config['settings']['general']['SITE_ID'] = $site->id;
				$config['settings']['general']['SITE_KEY'] = strtolower($site->key);
				$config['settings']['general']['SITE_NAME'] = $site->name;
				$config['settings']['general']['SITE_ENVIRONMENT'] = $site->environment; // Generally production, staging, or development

				/**
				 * The old app directory will be under the framework sites directory ($dir_app_key), whereas newer sites will be located in a directory named for the site ($dir_app_domain)
				 **/
				$dir_app_domain	= $config['settings']['general']['DIR_FS_FRAMEWORK_SITES'] . $config['settings']['general']['DOMAIN'] . DIRECTORY_SEPARATOR;
				$dir_app_key	= $config['settings']['general']['DIR_FS_FRAMEWORK_SITES'] . $config['settings']['general']['SITE_KEY'] . DIRECTORY_SEPARATOR;

				// Assume we are using the new way of doing things, with
				if (file_exists($dir_app_domain)) {

					$config['settings']['general']['DIR_FS_FRAMEWORK_APP'] = $dir_app_domain;
					$debug[] = 'Using domain directory: ' . $dir_app_domain;

					// Fall back to app key directory
				} else {

					$config['settings']['general']['DIR_FS_FRAMEWORK_APP'] = $dir_app_key;
					$debug[] = 'Falling back to app key directory: ' . $dir_app_key;

				}
			}
		}

		FrameworkManager::markTime(__class__ . '->initializeSite() after domain check');

		if (isset($config['settings']['general']['SITE_ENVIRONMENT']) && in_array($config['settings']['general']['SITE_ENVIRONMENT'], array('staging','development'))) {
			ini_set('display_errors', 1);
			error_reporting(E_ALL);
		} else {
			ini_set('display_errors', 0);
		}

		//if (!ConfigurationManager::addConfigFile(ConfigurationManager::get('DIR_FS_FRAMEWORK_APP') . 'config/config.xml', 'app')) die("We'll be right back!\n<!-- // Error: Unable to Load Site Configuration // -->");
		$dir_fs_framework_app = (isset($config['settings']['general']['DIR_FS_FRAMEWORK_APP'])) ? $config['settings']['general']['DIR_FS_FRAMEWORK_APP'] : null;

		$debug[] = '$dir_fs_framework_app: ' . $dir_fs_framework_app . '; Exists: ' . (file_exists($dir_fs_framework_app) ? 'Yes' : 'No');

		if (file_exists($dir_fs_framework_app)) {

			 PathManager::addFirst($config['settings']['general']['DIR_FS_FRAMEWORK_APP']);

			// File path for site configuration
			$file_config_site = $dir_fs_framework_app . 'config' . DIRECTORY_SEPARATOR . 'config.php';
			$file_config_site_alt = $dir_fs_framework_app . 'config' . DIRECTORY_SEPARATOR . 'config.xml';

			// Placeholder for CWI_XML_Traversal object
			$xml_config_site = null;

			/////////////////////////

			$config_cache_explanation = array();

			$active_cached_config = false;


			FrameworkManager::markTime(__class__ . '->initializeSite() after site cache check');

			/**
			 * Allow new config format
			 */
			if (file_exists($file_config_site)) {

				$config->merge( new LegacyConfig(include $file_config_site) );

				/**
				 * Check whether old configuration format exists for legacy sites
				 */
			} else if (file_exists($file_config_site_alt)) {

				$xml_config_site = ConfigurationManager::getConfigFileXml($file_config_site_alt);

				$config->merge( new LegacyConfig(ConfigurationManager::convertConfigXmlToArray($xml_config_site)   ));

			}

			FrameworkManager::markTime(__class__ . '->initializeSite() after loading site config');

		}

		############################

		// Load additional configuration settings directly from the database
		if (ConnectionManager::hasConnection()) {

			ConfigurationManager::addConfigSettingsFromDb($config);

			FrameworkManager::markTime(__class__ . '->initializeSite() after loading site db config');

			$enable_error_capture = (isset($config['settings']['custodian']['enable']) && true === $config['settings']['custodian']['enable'] || 'true' == strtolower($config['settings']['custodian']['enable']));

			if ($enable_error_capture) Custodian::enableCaptureErrors();

		}

		$debug[] = 'ConnectionManager::hasConnection: ' . (ConnectionManager::hasConnection() ? 'Yes':'No');

		DatabaseManager::finalizeTableSettings();

//		echo '<pre>';print_r($debug);exit;

		self::$isSiteInitialized = true;
	}
	/**
	 * Logs a message to a framework temp log (possibly phasing out in favor of Custodian::log())
	 *
	 * @param string $log_level The log level of the message to be stored.
	 * @param string $message The message to be stored to the log.
	 * @return boolean Whether or not the message was stored.  Returns false if either the message is not within the system_log_level, or if the file cannot be written to
	 */
	public static function log($log_level, $message=null) {
		$system_log_level = ConfigurationManager::get('LOG_LEVEL');
		if (empty($system_log_level)) $system_log_level = LOGLEVEL_EMERGENCY;

		$track_log_levels = array();

		switch ($system_log_level) {
			case LOGLEVEL_EMERGENCY:
				$track_log_levels = array(LOGLEVEL_EMERGENCY);
				break;
			case LOGLEVEL_ALERT:
				$track_log_levels = array(LOGLEVEL_EMERGENCY, LOGLEVEL_ALERT);
				break;
			case LOGLEVEL_CRITICAL:
				$track_log_levels = array(LOGLEVEL_EMERGENCY, LOGLEVEL_ALERT, LOGLEVEL_CRITICAL);
				break;
			case LOGLEVEL_ERROR:
				$track_log_levels = array(LOGLEVEL_EMERGENCY, LOGLEVEL_ALERT, LOGLEVEL_CRITICAL, LOGLEVEL_ERROR);
				break;
			case LOGLEVEL_WARNING:
				$track_log_levels = array(LOGLEVEL_EMERGENCY, LOGLEVEL_ALERT, LOGLEVEL_CRITICAL, LOGLEVEL_ERROR, LOGLEVEL_WARNING);
				break;
			case LOGLEVEL_NOTICE:
				$track_log_levels = array(LOGLEVEL_EMERGENCY, LOGLEVEL_ALERT, LOGLEVEL_CRITICAL, LOGLEVEL_ERROR, LOGLEVEL_WARNING, LOGLEVEL_NOTICE);
				break;
			case LOGLEVEL_INFO:
				$track_log_levels = array(LOGLEVEL_EMERGENCY, LOGLEVEL_ALERT, LOGLEVEL_CRITICAL, LOGLEVEL_ERROR, LOGLEVEL_WARNING, LOGLEVEL_NOTICE, LOGLEVEL_INFO);
				break;
			case LOGLEVEL_DEBUG:
				$track_log_levels = array(LOGLEVEL_EMERGENCY, LOGLEVEL_ALERT, LOGLEVEL_CRITICAL, LOGLEVEL_ERROR, LOGLEVEL_WARNING, LOGLEVEL_NOTICE, LOGLEVEL_INFO, LOGLEVEL_DEBUG);
				break;
		}

		if (in_array($log_level, $track_log_levels)) {
			#$log_file = ConfigurationManager::get('DIR_FS_TMP') . 'dump.log';
			$log_file = self::getTmpFile('framework-dump.log');

			if ($fp = @fopen($log_file, 'a+')) {

				$log_message = date('Y-m-d H:i:s') . ' [log_level=' . $log_level . '] ';
				if ($user = Membership::getUser()) {
					$log_message .= '[membership_id=' .$user->getId() . '] ';
				}
				$log_message .= $message;
				$log_message .= "\n";

				fwrite($fp, $log_message, strlen($log_message));
				fclose($fp);
				return true;
			}

		}

		return false;
	}
	/**
	 * A shorthand method for calling FrameworkManager::log(LOGLEVEL_DEBUG, $message)
	 * @param string $message
	 * @return boolean Whether or not the message can be saved.  See FrameworkMananager::log()
	 */
	public static function debug($message) {
		return FrameworkManager::log(LOGLEVEL_DEBUG, $message);
	}
}

?>
