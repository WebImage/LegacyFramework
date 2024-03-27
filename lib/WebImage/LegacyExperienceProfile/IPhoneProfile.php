<?php

namespace WebImage\LegacyExperienceProfile;

class IPhoneProfile extends AbstractProfile {
	/**
	 * Check if this profile is explicitly supported.  A profile might be supported implicitly via its inclusion in another IProfile via "addSupportedProfiles," but this method will check if this profile is actually supported in the current environment
	 * @return bool
	 */
	protected function checkIfSupported() {

		$user_agent = $_SERVER['HTTP_USER_AGENT'];
		$user_agent_lower = strtolower($user_agent);

		$is_supported = false;
		if (strpos($user_agent_lower, 'ipod') !== false || strpos($user_agent_lower, 'iphone') || \Page::get('testprofile') == 'iphone') {
			$is_supported = true;
		}
		return $is_supported;

	}
}
