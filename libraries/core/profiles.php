<?php

/**
 * Responsibility has been deligated to \WebImage\ExperienceProfile\ProfileManager
 * Class Profiles
 */
class Profiles {

	// Common methods
	/**
	 * @return WebImage\ExperienceProfile\ProfileManager
	 * @throws Exception
	 */
	public static function getInstance() {
		$service_manager = FrameworkManager::getApplication()->getServiceManager();
		$profile_manager = $service_manager->get('ExperienceProfileManager');
		return $profile_manager;
	}
	public static function init() {}

	public static function addProvider($config) {
		return static::getInstance()->addProviderConfig($config);
	}
	
	public static function setDefaultProvider($provider) {
		return static::getInstance()->setDefaultProvider($provider);
	}
	
	public static function getDefaultProvider() {
		return static::getInstance()->getDefaultProvider();
	}
	public static function isProfilingActive() {
		return static::getInstance()->isProfilingActive();
	}

	/**
	 * @param null $provider_name
	 * @return mixed|\WebImage\ExperienceProfile\IProfile
	 */
	public static function getProvider($provider_name=null) {
		return static::getInstance()->getProvider($provider_name);
	}

	#private static function getCurrentProfile() {
	#	return static::getInstance()->getCurrentProfileName();
	#}
	private static function setCurrentProfile($profile_name) {
		return static::getInstance()->setCurrentProfileByName($profile_name);
	}
	
	public static function getProviders() {
		return static::getInstance()->getProviders();
	}
	// Profiles specific methods
	public static function getCurrentProfileName() {
		return static::getInstance()->getCurrentProfileName();
	}
	
	public static function setCurrentProfileByName($profile_name) {
		return static::getInstance()->setCurrentProfileByName($profile_name);
	}
	
	public static function resetCurrentProfile() {
		return static::getInstance()->resetCurrentProfile();
	}
}
