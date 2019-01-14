<?php

/**
 * 09/18/2010	(Robert Jones) Modified translate to support ~base/ and ~app/ in addition to ~/ so that known file locations can be accessed more quickly
 * 10/07/2012	(Robert Jones) Modified getInstance() to use Singleton class (which allows it to be reset at any time)
 */
class PathManager {
	var $paths = array();
	
	public static function getInstance() {
		$instance = Singleton::getInstance('PathManager');
		return $instance;
	}
	/*function add($shortcut, $path) {
		$_this = PathManager::getInstance();
		$_this->paths[$shortcut] = $path;
	
	}*/
	public static function add($path) {
		$_this = PathManager::getInstance();
		array_push($_this->paths, $path);
	}
	public static function addFirst($path) {
		$_this = PathManager::getInstance();
		array_unshift($_this->paths, $path);
	}
	
	public static function translate($full_path) {
		
		$_this = PathManager::getInstance();
		
		// Get any configuration values
		if (preg_match_all('/%(.+?)%/', $full_path, $matches)) { // Can't use ConfigurationManager::getValueFromString because this may be called before Configuration is initiated
			if (isset($matches[1])) {
				foreach($matches[1] as $match) {
					$get_value = $match;
					if ($value = ConfigurationManager::get($get_value)) {
						$full_path = str_replace('%'.$get_value.'%', $value, $full_path);
					}
				}
			}
		}
		// Replace full path with appropriate directory separators
		$full_path = str_replace('/', DIRECTORY_SEPARATOR, $full_path);
		
		if (substr($full_path, 0, 1) == '~') {
			
			// Base paths
			if (substr($full_path, 1, 5) == 'base' . DIRECTORY_SEPARATOR) {
				$temp_path = ConfigurationManager::get('DIR_FS_FRAMEWORK_BASE') . substr($full_path, 6);
				if (file_exists($temp_path)) return $temp_path;
			// App paths
			} else if (substr($full_path, 1, 4) == 'app' . DIRECTORY_SEPARATOR) {
				$temp_path = ConfigurationManager::get('DIR_FS_FRAMEWORK_APP') . substr($full_path, 5);
				if (file_exists($temp_path)) return $temp_path;
			
			// Plugin-based paths - in the format "~plugin.[plugin_name]/path/to/file.php
			} else if (substr($full_path, 1, 7) == 'plugin.') {
				
				$slash_at = strpos($full_path, DIRECTORY_SEPARATOR, 7);
				$plugin_name = substr($full_path, 8, $slash_at-8);
				$sub_path = substr($full_path, $slash_at+1);
				
				FrameworkManager::loadLogic('plugin');
				
				if ($plugin_struct = PluginLogic::getInstalledPluginByName($plugin_name)) {
					$plugin_base = $plugin_struct->path;
					$plugin_path = $plugin_base . $sub_path;
					return $plugin_path;
				}
			// Search all locations (not including plugins)
			} else if (substr($full_path, 1, 1) == DIRECTORY_SEPARATOR) {
				foreach($_this->paths as $path) {
					$length_to_replace = 2;
					$temp_path = $path . substr($full_path, $length_to_replace, strlen($full_path)-$length_to_replace);
					if (file_exists($temp_path)) return $temp_path;
				}
			}
		} else if (file_exists($full_path)) {
			return $full_path;
		}

		return false;
	}
	
	/**
	 * Uses shortcut, e.g. ~/plugins/ to return all files within any paths, be they app, base, or custom paths
	 * @param string The directory
	 */
	public static function getAllDirFiles($dir) {
		$file_stack = array();
		
		$check_paths = array();
		if (substr($dir, 0, 2) == '~/') {
			$paths = PathManager::getPaths();
			foreach($paths as $path) {
				array_push($check_paths, $path . substr($dir, 2));		
			}
		} else {
			array_push($check_paths, $dir);
		}

		foreach($check_paths as $check_path) {
			if (file_exists($check_path)) {
				$dh = opendir($check_path);
			
				while ($file=readdir($dh)) {
					if (!in_array($file, array('.', '..'))) {
						if (filetype($check_path . $file) == 'dir') $file .= DIRECTORY_SEPARATOR;
						array_push($file_stack, $check_path . $file);
					}
				}
				closedir($dh);
			}
		}
		sort($file_stack);
		return $file_stack;
	}
	
	public static function getAdminContentPath($path) {
		$base = substr(ConfigurationManager::get('DIR_WS_ADMIN_CONTENT'), 0, strlen(ConfigurationManager::get('DIR_WS_ADMIN_CONTENT'))-1);
		return $base . $path;
	}
	
	public static function getPaths() {
		$_this = PathManager::getInstance();
		return $_this->paths;
	}
	
	public static function getPath() {
		if (isset($_SERVER['REQUEST_URI']) && ($path_parts = parse_url($_SERVER['REQUEST_URI']))) return $path_parts['path'];
		else return false;
	}
	public static function getQueryString() {
		if (isset($_SERVER['REQUEST_URI']) && ($path_parts = parse_url($_SERVER['REQUEST_URI']))) return (isset($path_parts['query']) ? $path_parts['query'] : '');
		else return false;
		#return $_SERVER['QUERY_STRING'];		
	}
	
	public static function getCurrentUrl() {
		$domain = '';
		if (isset($_SERVER['SERVER_NAME']) && !empty($_SERVER['SERVER_NAME'])) {
			$domain = $_SERVER['SERVER_NAME'];
		} else if (isset($_SERVER['HTTP_HOST']) && !empty($_SERVER['HTTP_HOST'])) {
			$domain = $_SERVER['HTTP_HOST'];
		}
		
		$url = '';
		if (!empty($domain)) {
			$url = $_SERVER['REQUEST_SCHEME'] . '://' . $domain;
		}
		$url .= PathManager::getPath();
		$query = PathManager::getQueryString();
		if (!empty($query)) $url .= '?' . $query;
		
		return $url;
	}
	
	public static function getNonSecureUrl($path=null) {
		if (is_null($path)) {
			$path = PathManager::getCurrentUrl();
		}
		
		// If current page is secure, return the full non-secure URL - may remove the IF in the future if it becomes a problem for reusability
		if (Page::isSecure()) $path = 'http://' . ConfigurationManager::get('DOMAIN') . $path;
		
		return $path;
	}
	
	public static function getSecureUrl($path=null) {
		if (is_null($path)) {
			$path = PathManager::getCurrentUrl();
		}
		
		// If current page is not secure, return full secure URL - may remove the IF in the future if it becomes a problem for reusability
		if (!Page::isSecure()) $path = 'https://' . ConfigurationManager::get('DOMAIN') .$path;
		
		return $path;
	}
	
}


?>