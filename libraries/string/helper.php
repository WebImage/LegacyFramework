<?php

class CWI_STRING_Helper {
	
	public static function strToMachineKey($str) {
		$url_to_use = strtolower($str);
		$url_to_use = preg_replace('/^the | is | of | a | am /', ' ', $url_to_use);
		$url_to_use = str_replace('_', '-', $url_to_use);
		$url_to_use = preg_replace('/[^0-9a-z \-]+/', '', $url_to_use);
		$url_to_use = preg_replace('/[ ]+/', '-', $url_to_use);
		$url_to_use = preg_replace('/^-+/', '', $url_to_use); // Remove prepended dashes
		return $url_to_use;
	}
	/*
	 * Deprecated
	 * Kept for legacy
	 **/
	public static function strToSefKey($str) { return self::strToMachineKey($str); }
}

?>