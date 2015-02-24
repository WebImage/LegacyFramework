<?php

class ServiceManagerConfig implements IServiceManagerConfig {
	/**
	 * @var array
	 */
	protected $config = array();

	public function __construct($config = array()) {
		$this->config = $config;
	}

	/**
	 * Get allow override
	 *
	 * @return null|bool
	 */
	/*
	public function getAllowOverride() {
		return (isset($this->config['allowOverride'])) ? $this->config['allowOverride'] : null;
	}
	*/
	/**
	 * Get factories
	 *
	 * @return array
	 */
	public function getFactories() {
		return (isset($this->config['factories'])) ? $this->config['factories'] : array();
	}
	/*
	public function getAbstractFactories() {
        return (isset($this->config['abstract_factories'])) ? $this->config['abstract_factories'] : array();
	}
	public function getInvokables() {
        return (isset($this->config['invokables'])) ? $this->config['invokables'] : array();
	}
	public function getServices() {
        return (isset($this->config['services'])) ? $this->config['services'] : array();
	}
	*/
	public function getAliases() {
		return (isset($this->config['aliases'])) ? $this->config['aliases'] : array();
	}
	/*
	public function getInitializers() {
        return (isset($this->config['initializers'])) ? $this->config['initializers'] : array();
	}
	*/
	public function getShared() {
		return (isset($this->config['shared'])) ? $this->config['shared'] : array();
	}
	/*
	public function getDelegators() {
        return (isset($this->config['delegators'])) ? $this->config['delegators'] : array();
	}
	*/

	public function configureServiceManager(ServiceManager $service_manager) {

		/*if (null !== ($allowOverride = $this->getAllowOverride())) {
			$service_manager->setAllowOverride($allowOverride);
		}*/

		// Setup factories
		foreach($this->getFactories() as $name => $factory) {
			$service_manager->setFactory($name, $factory);
		}

		// Setup aliases
		foreach($this->getAliases() as $alias => $name) {
			$service_manager->setAlias($alias, $name);
		}

		// Setup shared
		foreach($this->getShared() as $name => $is_shared) {
			$service_manager->setShared($name, $is_shared);
		}


	}
}