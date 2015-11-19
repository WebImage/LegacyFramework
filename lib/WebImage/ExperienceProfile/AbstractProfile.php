<?php

namespace WebImage\ExperienceProfile;

use WebImage\Provider\AbstractProvider;

abstract class AbstractProfile extends AbstractProvider implements IProfile {
	/**
	 * The name of supported profiles that are related to this profile.  Typically added automatically via Profile class
	 * @var array
	 */
	protected $supportedProfiles = array();
	public static function createFromPageRequest($page_request) { return false; }

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

}