<?php

namespace WebImage\LegacyExperienceProfile;

use WebImage\Provider\IProvider;

interface IProfile extends IProvider {

	/**
	 * Adds the name of another supported profile to this profile
	 * @param string $profile_name
	 * @return void
	 */
	public function addSupportedProfiles($profile_name);

	/**
	 * Whether or not this profile provider supports a specific $provider_name
	 * @param $profile_name
	 * @return bool
	 */
	public function supportsProfile($profile_name);

	/**
	 * Check if this profile is explicitly supported.  A profile might be supported implicitly via its inclusion in another IProfile via "addSupportedProfiles," but this method will check if this profile is actually supported in the current environment
	 * @param string $domain The domain name to check this request against
	 * @return bool
	 */
	public function isSupported();
	/**
	 * Get a list of supported profiles
	 * @return array
	 */
	public function getSupportedProfiles();

	/**
	 * @param string $domain
	 * @return mixed
	 */
	public function addSupportedDomain($domain);

	/**
	 * @return mixed
	 */
	#public function getSupportedDomains();
	#public function isSupportedDevice();
	/**
	 * @param $domain
	 * @return bool
	 */
	public function supportsDomain($domain);

	/**
	 * Get the ProfileManager that is managing this Profile
	 * @return null|ProfileManager
	 */
	public function getProfileManager();

	/**
	 * Set the ProfileManager that is managing this profile
	 * @param ProfileManager $profile_manager
	 * @return self
	 */
	public function setProfileManager(ProfileManager $profile_manager);

}
