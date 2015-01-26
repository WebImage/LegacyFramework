<?php

/**
 * Used as a quick fix for implementing lazy initialization of providers
 */
class LazyProvider {
	private $config;
	private $initializedClass;
	public function __construct(ProviderConfiguration $config, $initialized_class=null) {
		$this->config = $config;
		$this->initializedClass = $initialized_class;
	}
	public function getInitializedClass() {

		if (is_null($this->initializedClass)) {
			$class_file = $this->config->get('classFile');
			$class_name = $this->config->get('className');

			include_once($class_file);
			
			$this->initializedClass = new $class_name;
			$this->initializedClass->init($this->config->get('name'), $this->config);
		}
		return $this->initializedClass;
	}
}