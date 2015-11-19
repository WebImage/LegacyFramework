<?php

namespace WebImage\ExperienceProfile;

use WebImage\Provider\IProvider;

interface IProfile extends IProvider {

	/**
	 * Factory method to create self
	 * @param $page_request
	 * @return mixed
	 */
	public static function createFromPageRequest($page_request);

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
	 * Get a list of supported profiles
	 * @return array
	 */
	public function getSupportedProfiles();

}