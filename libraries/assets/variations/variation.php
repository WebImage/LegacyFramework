<?php

// Representation of asset variation configuration
class CWI_ASSETS_VARIATIONS_Variation {
	private $key, $isAuto;
	
	function __construct() {
		$this->isAuto = false;
	}
	
	public function getKey() { return $this->key; }
	public function setKey($key) { $this->key = $key; }
	
	public function isAuto($true_false=null) { 
		if (is_null($true_false)) { // Getter
			return $this->isAuto;
		} else if (is_bool($true_false)) { // Setter
			$this->isAuto = $true_false;
		} else { // Error
			throw new Exception('Invalid value passed to isAuto()');
		}
	}
}
