<?php
/**
 * 02/02/2010	(Robert Jones) Added $default_value parameter to SessionManager::get($name, $default_value)
 * 07/28/2010	(Robert Jones) Added getCookie(), setCookie(), delCookie()
 * 06/28/2016	(Robert Jones) Modified how SessionManager is instantiated so that it is not actually initiated until required
 */
class SessionManager {
	var $vars;
	private $isInitialized = false;
	public static function getInstance() {
		$instance = Singleton::getInstance('SessionManager');

		if (!$instance->isInitialized) {
			#ini_set('display_errors', 1);error_reporting(E_ALL);
			#throw new Exception('Session start');
			if (static::isSessionEnabled()) {
				session_start();
				foreach ($_SESSION as $key => $val) {
					$instance->vars[$key] = $val;
				}
			}
			$instance->isInitialized = true;
		}
		return Singleton::getInstance('SessionManager');
	}
	
	private static function isSessionEnabled() {
		return (ConfigurationManager::get('FRAMEWORK_MODE') != FRAMEWORK_MODE_CLI);
	}
	
	public static function set($name, $value, $serialize=false) {
		// Prepare for $serialize later
		$session_manager = SessionManager::getInstance();
		$session_manager->vars[SessionManager::_appizeSessionVar($name)] = $value;
		
		if (static::isSessionEnabled()) {
			$_SESSION[SessionManager::_appizeSessionVar($name)] = $value;
		}
	}
	
	public static function get($name, $default=false) {
		$session_manager = SessionManager::getInstance();
				
		if (isset($session_manager->vars[SessionManager::_appizeSessionVar($name)])) {
			return $session_manager->vars[SessionManager::_appizeSessionVar($name)];
		} else {
			return $default;
		}
	}
	
	public static function del($name) {
		$session_manager = SessionManager::getInstance();
		
		if (static::isSessionEnabled()) {
			unset($_SESSION[SessionManager::_appizeSessionVar($name)]);
		}
		
		unset($session_manager->vars[SessionManager::_appizeSessionVar($name)]);
	}
	
	public static function destroy() {
		$session_manager = SessionManager::getInstance(); // Ensures that a session is in existence so that session_destroy does not throw an error
		$session_manager->vars = array();
		
		if (static::isSessionEnabled()) {
			$_SESSION = array();
			session_destroy();
		}
	}
	
	public static function getId() {
		SessionManager::getInstance();
		
		return (static::isSessionEnabled()) ? session_id() : 0;
	}
	
	private static function _appizeSessionVar($name) {
		$domain_key = preg_replace('#[^a-z0-9]#', '', ConfigurationManager::get('DOMAIN'));
		return $domain_key . '_' . $name;
	}
	
	/** 
	 * Cookie convenience functions
	 */
	public static function setCookie($name, $value='', $expire=0, $path='', $domain='', $secure=false, $http_only=false) {
		return setcookie(self::_appizeSessionVar($name), $value, $expire, $path, $domain, $secure, $http_only);
	}
	
	public static function getCookie($name, $default=false) {
		if (isset($_COOKIE[self::_appizeSessionVar($name)])) {
			return $_COOKIE[self::_appizeSessionVar($name)];
		} else return $default;
	}
	
	public static function delCookie($name) {
		$name = self::_appizeSessionVar($name);
		setcookie($name, '', time()-(25*3600));
		unset($_COOKIE[$name]);
	}
}