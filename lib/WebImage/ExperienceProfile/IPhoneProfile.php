<?php

namespace WebImage\ExperienceProfile;

class IPhoneProfile extends AbstractProfile {
	public static function createFromPageRequest($request_handler) {
		$user_agent		= $_SERVER['HTTP_USER_AGENT'];
		$user_agent_lower	= strtolower($user_agent);


		if (strpos($user_agent_lower, 'ipod')!== false || strpos($user_agent_lower, 'iphone') || \Page::get('testprofile')=='iphone') {
			return new self;
		} else return false;
	}
}