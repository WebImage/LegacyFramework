<?php

namespace WebImage\ExperienceProfile;

use ConfigurationManager;
use SessionManager;
use Page;
use WebImage\Core\Collection;
use WebImage\Provider\Config as ProviderConfig;
use WebImage\Core\Dictionary;

class ProfileManager {

	private $currentProfile;
	private $providerConfigs = array();
	private $initedProviders = array();
	private $defaultProviderName;
	/**
	 * @var \WebImage\Core\Dictionary
	 */
	private $domainMapping;

	public function __construct() {
		$this->domainMapping = new Dictionary();
	}
	public function addProviderConfig(ProviderConfig $config) {
		$this->providerConfigs[$config->getName()] = $config;
	}

	public function setDefaultProviderName($provider_name) {
		$this->defaultProviderName = $provider_name;
	}

	public function getDefaultProviderName() {
		return $this->defaultProviderName;
	}

	/**
	 * Check that profiling is activated in settings
	 * @return bool
	 */
	public function isProfilingActive() {
		if ($enable_profiles = strtolower(ConfigurationManager::get('ENABLE_PROFILES'))) {
			if ($enable_profiles == 'true') return true;
			else return false;
		} else return false;
	}

	/**
	 * Map a domain name to a specific profile name
	 * @param string $domain
	 * @param string $profile_name
	 */
	public function setDomainProfile($domain, $profile_name) {
		$this->domainMapping->set($domain, $profile_name);
	}

	/**
	 * Check if a domain has been mapped to a domain
	 * @param string $domain
	 * @return string|null
	 */
	public function getProfileNameByDomain($domain) {
		return $this->domainMapping->get($domain);
	}

	/**
	 * Get a profile associated with a domain
	 * @param $domain
	 * @return mixed|null|IProfile
	 */
	public function getProfileByDomain($domain) {
		$profile_name = $this->getProfileNameByDomain($domain);
		$profile = null;

		if (null !== $profile_name) {
			$domain_profile = $this->getProvider($profile_name);
			// Make sure the matached profile supports this domain
			if (null !== $domain_profile) if ($domain_profile->supportsDomain($domain)) $profile = $domain_profile;
		}

		return $profile;
	}

	/**
	 * Get a list of domains that are mapped to a profile
	 * @param $profile_name
	 * @return array
	 */
	public function getDomainsForProfile($profile_name) {
		$all = $this->domainMapping->getAll();
		/** @var \WebImage\Core\DictionaryField $field */
		$domains = array();

		while($field = $all->getNext()) {
			$domain = $field->getKey();
			$p_name = $field->getDefinition();
			if ($p_name == $profile_name) $domains[] = $domain;

		}
		return $domains;
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
		if (null === $provider_name) {

			/**
			 * If a current profile is not already set, load it here.
			 */
			$profile = $this->getCurrentProfile();
			if (null !== $profile) return $profile;

			// Check session for profile
			if (null === $profile) $profile = $this->getSessionProfile();
			// Is domain mapped to profile?
			if (null === $profile) $profile = $this->getProfileByDomain( ConfigurationManager::get('DOMAIN') );
			// Otherwise check for supported profiles
			if (null === $profile) $profile = $this->getFirstSupportedProfile();

			/**
			 * If profile is still not found, start falling back to default alternatives
			 */


			if (null === $profile) {
				$default_provider_name = $this->getDefaultProviderName();
				// make sure the default is not null, which would cause an infinite loop
				if (null !== $default_provider_name) $profile = $this->getProvider($default_provider_name);
			}

			$this->setCurrentProfile($profile);

			return $profile;

		} else if (isset($this->initedProviders[$provider_name])) {

			return $this->initedProviders[$provider_name];

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
						$requested_profile = new $class_name;
						$this->initProfile($requested_profile, $provider_config);
						return $requested_profile;
					}
				}
			}

			return;
		}

		if (!isset($this->initedProviders[$provider_name])) return;
		return $this->initedProviders[$provider_name];
	}

	private function initProfile(IProfile $profile, ProviderConfig $config) {

		// Make sure we do not get into an infinite circle
		static $circular_check = array();

		$provider_name = $config->getName();
		$this->initedProviders[$provider_name] = $profile;

		// No need to continue if already inited
		if (in_array($provider_name, $circular_check)) return;

		// Limit the domains that this profile supports
		$supported_domains = $config->getMetaValue('supportedDomains');
		if ($supported_domains) {
			foreach($supported_domains as $domain) {
				$profile->addSupportedDomain($domain);
			}
		}

		// Initialize profile
		$profile->init($provider_name, $config);
		$profile->setProfileManager($this);
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
			# Removed to allow caching // SessionManager::set('current_profile', $profile_name);
			SessionManager::setCookie('current_profile', $profile_name);
			$this->setCurrentProfile($provider);
		} else {
			return false;
		}

		return true;
	}

	public function resetCurrentProfile() {
		# Removed to allow caching // SessionManager::del('current_profile');
		SessionManager::delCookie('current_profile');
	}

	private function getSessionProfile() {

		$profile = null;

		/**
		 * REMOVED TO ALLOW CACHING // Check session for current_profile
		 */
		/**
		 if ($session_current_profile = SessionManager::get('current_profile')) {
			$profile = $this->getProvider($session_current_profile);
		}
		if (null !== $profile) return $profile;
		*/
		/**
		 * Check cookie for current profile
		 */
		if ($cookie_current_profile = SessionManager::getCookie('current_profile')) {
			$profile = $this->getProvider($cookie_current_profile);
		}
		return $profile;
	}

	private function getFirstSupportedProfile() {
		// Otherwise search for possible matches
		$provider_configs = $this->getProviderConfigs();

		$domain = ConfigurationManager::get('DOMAIN');

		$profile = null;
		/**
		 * @var string $provider_name
		 * @var \WebImage\Provider\Config $provider_config
		 */
		foreach ($provider_configs as $provider_name => $provider_config) {

			$provider = $this->getProvider($provider_name);

			if (null !== $provider) {

				if ($provider->isSupported()) {

					$profile = $provider;
					break;

				}
			}

		}

		return $profile;
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

	public function getProviders() {

		$configs = $this->getProviderConfigs();
		$providers = new Collection();
		foreach($configs as $provider_name => $config) {
			$provider = $this->getProvider($provider_name);
			if (null !== $provider) {
				$providers->add($provider);
			}
		}
		return $providers;
	}
}
