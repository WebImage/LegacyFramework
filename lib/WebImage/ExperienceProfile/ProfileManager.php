<?php

namespace WebImage\ExperienceProfile;

use ConfigurationManager;
use SessionManager;
use Page;
use WebImage\Provider\Config as ProviderConfig;

class ProfileManager {

	private $currentProfile;
	private $providerConfigs = array();
	private $defaultProvider;

	public function addProviderConfig(ProviderConfig $config) {
		$this->providerConfigs[$config->getName()] = $config;
	}

	public function setDefaultProvider($provider) {
		$this->defaultProvider = $provider;
	}

	public function getDefaultProvider() {
		return $this->defaultProvider;
	}
	public function isProfilingActive() {
		if ($enable_profiles = strtolower(ConfigurationManager::get('ENABLE_PROFILES'))) {
			if ($enable_profiles == 'true') return true;
			else return false;
		} else return false;
	}

	/**
	 * @param null $provider_name
	 * @return mixed|IProfile
	 */
	public function getProvider($provider_name=null) {

		// Make sure profiles is active
		if (!$this->isProfilingActive()) return;

		/**
		 * SCENARIO #1 - provider_name not passed
		 */
		if (is_null($provider_name)) {

			/**
			 * If a current profile is not already set, load it here.
			 */
			$current_profile = $this->getCurrentProfile();

			if ($current_profile) return $current_profile;
			else {

				/**
				 * Check session for current_profile
				 */
				if ($session_current_profile = SessionManager::get('current_profile')) {
					if ($session_profile = $this->getProvider($session_current_profile)) return $session_profile;
				}

				/**
				 * Check cookie for current profile
				 */
				if ($cookie_current_profile = SessionManager::getCookie('current_profile')) {
					if ($cookie_profile = $this->getProvider($cookie_current_profile)) return $cookie_profile;
				}

				// Otherwise search for possible matches
				$provider_configs = $this->getProviderConfigs();

				if ($request_handler = Page::getRequestHandler()) {

					/**
					 * @var string $provider_name
					 * @var \WebImage\Provider\Config $provider_config
					 */
					foreach($provider_configs as $provider_name => $provider_config) {

						$class_name = $this->getNormalizedClassName($provider_config->getClassName());
						$class_file = \PathManager::translate($provider_config->getClassFile());

						if (!empty($class_name)) {

							// If class does not exist, see if a $class_file was explicitly specified
							if (!class_exists($class_name) && !empty($class_file)) @include_once($class_file);

							if (class_exists($class_name)) {

								if ($current_profile = call_user_func(array($class_name, 'createFromPageRequest'), $request_handler)) {

									$this->initProfile($current_profile, $provider_config);
									$this->setCurrentProfile($current_profile);

									break;

								}

							}
						}

					}
				}

			}

			// If the profile is still not found, get the default provider
			if (!$current_profile = $this->getCurrentProfile()) {
				return $this->getProvider($this->getDefaultProvider());
			}

			return $current_profile;

		} else {

			$provider_configs = $this->getProviderConfigs();

			if (isset($provider_configs[$provider_name])) {

				/** @var \WebImage\Provider\Config $provider_config */
				$provider_config = $provider_configs[$provider_name];

				$class_name = $this->getNormalizedClassName($provider_config->getClassName());

				$class_file = \PathManager::translate($provider_config->getClassFile());

				if (!empty($class_name)) {

					if (!class_exists($class_name) && !empty($class_file)) @include_once($class_file);

					if (class_exists($class_name)) {
						$requested_profile = new $class_name();
						$this->initProfile($requested_profile, $provider_config);
						return $requested_profile;
					}
				}
			}

			return;
		}

		if (!isset($this->providers[$provider_name])) return;
		return $this->providers[$provider_name];
	}

	private function initProfile(IProfile $profile, ProviderConfig $config) {

		// Make sure we do not get into an infinite circle
		static $circular_check = array();

		$provider_name = $config->getName();

		// No need to continue if already inited
		if (in_array($provider_name, $circular_check)) return;

		// Initialize profile
		$profile->init($provider_name, $config);
		$profile->addSupportedProfiles($provider_name);

		// Add provider name to $circular_check
		$circular_check[] = $provider_name;

		/**
		 * Get all supported profiles from "supportedProfiles" and add to this profile's supported profiles
		 */
		$config_supported = $config->getMetaValue('supportedProfiles');

		if ($config_supported) {

			foreach($config_supported as $name) {

				$supported_profile = static::getProvider($name);

				// Check if provider was found
				if (null !== $supported_profile) {

					// Add to supported profiles
					$profile->addSupportedProfiles($name);

					// Get the supported profile's supported profiles and add
					foreach($supported_profile->getSupportedProfiles() as $p) {

						$profile->addSupportedProfiles($p);

					}
				}

			}

		}

		return $profile;
	}
	private function getCurrentProfile() {
		return $this->currentProfile;
	}
	private function setCurrentProfile($profile) {
		$this->currentProfile = $profile;
	}


	public function getProviderConfigs() {
		return $this->providerConfigs;
	}
	// Profiles specific methods
	public function getCurrentProfileName() {
		if ($provider = $this->getProvider()) {
			return $provider->getName();
		} else {
			return 'Default';
		}
	}

	public function setCurrentProfileByName($profile_name) {
		if ($provider = $this->getProvider($profile_name, false)) {
			SessionManager::set('current_profile', $profile_name);
			SessionManager::setCookie('current_profile', $profile_name);
			$this->setCurrentProfile($provider);
		} else {
			return false;
		}

		return true;
	}

	public function resetCurrentProfile() {
		SessionManager::del('current_profile');
		SessionManager::delCookie('current_profile');
	}

	/**
	 * Allow classes to be passed without root namespace slash
	 * @param $class
	 * @return string
	 */
	protected function getNormalizedClassName($class) {
		if (substr($class, 0, 1) != '\\') $class = '\\' . $class;
		return $class;
	}
}
