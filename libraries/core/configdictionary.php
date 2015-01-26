<?php

/**
 * Used primarily by by editable controls and assets that need to serialize stored values
 */
class ConfigDictionary extends Dictionary {
	public function toString() { 
		$storage = $this->getStorage();
		return serialize($storage);
	}
	public static function createFromString($serialized_value) {
		$array = array();
		if (!empty($serialized_value)) {
			$array = unserialize($serialized_value);
		}
		if (!is_array($array)) $array = array();
		return new ConfigDictionary($array);
	}
}