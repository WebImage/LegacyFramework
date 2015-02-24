<?php

class Profiles {
	private $currentProfile;
	var $m_providers = array();
	// Common methods
	public static function getInstance() { return Singleton::getInstance('Profiles'); }
	public static function init() {}

	public static function addProvider($config) {

		#$config->set('__classFileValid', true); // Assume true by default
		#$config->set('__classLoaded', false);
		#$config->set('__instantiatedObject', null);

		$_this = Profiles::getInstance();
		$name = $config->get('name');

		$_this->m_providers[$name] = $config;
	}
	
	public static function setDefaultProvider($provider) {
		$_this = Profiles::getInstance();
		$_this->m_defaultProvider = $provider;
	}
	
	public static function getDefaultProvider() {
		$_this = Profiles::getInstance();
		return $_this->m_defaultProvider;
	}
	public static function isProfilingActive() {
		if ($enable_profiles = strtolower(ConfigurationManager::get('ENABLE_PROFILES'))) {
			if ($enable_profiles == 'true') return true;
			else return false;
		} else return false;
	}
	 
	public static function getProvider($provider_name=null) {
		$_this = Profiles::getInstance();
		/**
		 * SCENARIO #1 - provider_name not passed
		 */
		if (is_null($provider_name)) {
			
			/**
			 * If a current profile is not already set, load it here.
			 */
			if (Profiles::isProfilingActive()) {
				
				$current_profile = $_this->getCurrentProfile();
				
				if ($current_profile) return $current_profile;
				else {
					
					// Check session for current_profile
					if ($session_current_profile = SessionManager::get('current_profile')) if ($session_profile = Profiles::getProvider($session_current_profile)) return $session_profile;
					
					// Check cookie for current profile
					if ($cookie_current_profile = SessionManager::getCookie('current_profile')) if ($cookie_profile = Profiles::getProvider($cookie_current_profile)) return $cookie_profile;
					
					// Otherwise search for possible matches
					$providers = $_this->getProviders();
					
					foreach($providers as $provider_name=>$provider_config) {
						if ($provider_config->get('className') && @include_once($provider_config->get('classFile'))) {
							$provider_class_name = $provider_config->get('className');
	
							if ($request_handler = Page::getRequestHandler()) {
								if ($current_profile = call_user_func(array($provider_class_name, 'createFromPageRequest'), $request_handler)) {
									$current_profile->setName($provider_config->get('name'));
									$current_profile->config = $provider_config;
									$_this->setCurrentProfile($current_profile);
									break;
								}
							}
						}
					}
					
				}
				// If the profile is still not found, get the default provider
				if (!$current_profile = $_this->getCurrentProfile()) {
					return $_this->getProvider($_this->getDefaultProvider());
				}
				
				return $current_profile;
			} else return false;
		} else {
			
			##############
			
			$providers = $_this->getProviders();
	
			if (isset($providers[$provider_name])) {
				$provider_config = $providers[$provider_name];
				
				if ($provider_config->get('className') && @include_once($provider_config->get('classFile'))) {
					$provider_class_name = $provider_config->get('className');
					
					if (class_exists($provider_class_name)) {
						$requested_profile = new $provider_class_name();
						$requested_profile->setName($provider_config->get('name'));
						$requested_profile->config = $provider_config;
						return $requested_profile;
					}
				}
			}
			##############
			return false;
		}

		if (!isset($_this->m_providers[$provider_name])) return false;
		return $_this->m_providers[$provider_name];
	}
	private static function getCurrentProfile() {
		$_this = Profiles::getInstance();
		if (!is_null($_this->currentProfile)) return $_this->currentProfile;
		else return false;
	}
	private static function setCurrentProfile($profile) {
		$_this = Profiles::getInstance();
		$_this->currentProfile = $profile;
	}

	
	public static function getProviders() {
		$_this = Profiles::getInstance();
		return $_this->m_providers;
	}
	// Profiles specific methods
	public static function getCurrentProfileName() {
		if ($provider = Profiles::getProvider()) {
			return $provider->getName();
		} else {
			return 'Default';
		}
	}
	
	public static function setCurrentProfileByName($profile_name) {
		if ($provider = Profiles::getProvider($profile_name, false)) {
			SessionManager::set('current_profile', $profile_name);
			SessionManager::setCookie('current_profile', $profile_name);
			Profiles::setCurrentProfile($provider);
		} else {
			return false;
		}
		
		return true;
	}
	
	public static function resetCurrentProfile() {
		SessionManager::del('current_profile');
		SessionManager::delCookie('current_profile');
	}
}
