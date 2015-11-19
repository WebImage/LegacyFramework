<?php

namespace WebImage\ExperienceProfile;

class DefaultProfile extends AbstractProfile {
	public static function createFromPageRequest($request_handler) {
		return new self;
	}
}