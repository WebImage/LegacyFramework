<?php

namespace WebImage\ControlCompiler\Result;

class ControlInitializer extends AbstractControlComponent {
	private $controlName, $instanceName, $params;
	/**
	 * Whether this is a root level of control and should be rendered
	 **/
	#private $isRootLevel = false;
	function __construct($control_name, $instance_name, \ControlConfigDictionary $params) {
		$this->controlName = $control_name;
		$this->instanceName = $instance_name;
		$this->params = $params;
	}
	public function getControlName() { return $this->controlName; }
	public function getInstanceName() { return $this->instanceName; }
	public function getParams() { return $this->params; }

}