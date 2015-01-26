<?php

// Representation of asset variation step configuration

class CWI_ASSETS_VARIATIONS_Step {
	private $method, $parameters;
	
	function __construct() {
		$this->parameters = new Collection();
	}
	
	public function addParameter(CWI_ASSETS_VARIATIONS_StepParameter $parameter) {
		$this->parameters->add($parameter);
	}
	
	public function getParameters() { return $this->parameters; }
}
