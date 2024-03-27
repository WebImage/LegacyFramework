<?php

namespace WebImage\LegacyExperienceProfile;

use WebImage\Provider\AbstractProvider;

abstract class AbstractProfile extends AbstractProvider implements IProfile {
	/**
	 * The name of supported profiles that are related to this profile.  Typically added automatically via Profile class
	 * @var array
	 */
	protected $supportedProfiles = array();
	/**
	 * @var array List of domains this profile supports
	 */
	protected $supportedDomains = array();
	/**
	 * @var ProfileManager
	 */
	#private $profileManager;

	/**
	 * Add a supported profile name
	 * @param $profile_name
	 */
	public function addSupportedProfiles($profile_name) {
		if (in_array($profile_name, $this->supportedProfiles)) return false;
		else {
			$this->supportedProfiles[] = $profile_name;
			return true;
		}
	}

	/**
	 * Whether or not this profile provider supports a specific $provider_name
	 * @param $profile_name
	 * @return bool
	 */
	public function supportsProfile($profile_name) {
		return (in_array($profile_name, $this->getSupportedProfiles()));
	}

	/**
	 * Get a list of supported profiles
	 * @return array
	 */
	public function getSupportedProfiles() { return $this->supportedProfiles; }

	/**
	 * Get the ProfileManager that is managing this Profile
	 * @return null|ProfileManager
	 */
	public function getProfileManager() {
		return $this->profileManager;
	}

	/**
	 * Set the ProfileManager that is managing this profile
	 * @param ProfileManager $profile_manager
	 * @return self
	 */
	public function setProfileManager(ProfileManager $profile_manager) {
		$this->profileManager = $profile_manager;
	}
	public function getSupportedDomains() { return $this->supportedDomains; }
	public function addSupportedDomain($domain) {
		$this->supportedDomains[] = $domain;
	}
	/**
	 * Check if this profile is explicitly supported.  A profile might be supported implicitly via its inclusion in another IProfile via "addSupportedProfiles," but this method will check if this profile is actually supported in the current environment
	 * @return bool
	 */
	private $isSupported;
	public function isSupported() {
		$domain = \ConfigurationManager::get('DOMAIN');

		$debug = false;
if ($debug) echo __METHOD__ . ': ' . $this->getName() . '<br />';
		if (null === $this->isSupported) {
if ($debug) echo '-- New lookup<br />';
			// Unsupported by default
			$is_supported = false;

			/**
			 * Check if the domain is mapped to this profile
			 */
			if ($this->isMappedToDomain($domain)) {
if ($debug) echo '-- Is Mapped to Domain<br />';
				$is_supported = true;
				/**
				 * Otherwise, check if the profile is explicitly supported
				 */
			} else {
if ($debug) echo '-- Is NOT mapped to domain<br />';
				$is_supported = $this->checkIfSupported();
				if (!is_bool($is_supported)) throw new \RuntimeException(sprintf('Call to %s verifySupported expected a boolean response', get_class($this)));

			}

			$this->isSupported = $is_supported;
		}

if ($debug) echo get_class($this) . ' -- Supported: ' . ($this->isSupported?'Yes':'No') . '<br />';

		return $this->isSupported;

	}
	public function isSupportedOnDomain($domain) {
		return ($this->isSupported() && $this->supportsDomain($domain));
	}
	protected function checkIfSupported() { return false; }

	public function supportsDomain($domain=null) {
		if (null === $domain || count($this->supportedDomains) == 0) return true;
		else return (in_array($domain, $this->supportedDomains));
	}

	private function getMappedDomains() {
		$manager = $this->getProfileManager();
		$domains = array();
		if (null !== $manager) {
			$domains = $manager->getDomainsForProfile($this->getName());
		}
		return $domains;
	}
	private function isMappedToDomain($domain=null) {
		$domains = $this->getMappedDomains();

		if (null === $domain) return false;
		else return (in_array($domain, $this->getMappedDomains()));
	}
}
