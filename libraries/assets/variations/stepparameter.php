<?php

// Representation of asset variation step parameter configuration
class CWI_ASSETS_VARIATIONS_StepParameter {
	private $name, $value;
	function __construct($name, $value) {
		$this->name = $name;
		$this->value = $value;
	}
}
