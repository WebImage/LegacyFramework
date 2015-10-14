<?php

namespace WebImage\ServiceManager;

use Exception;
use InvalidArgumentException;

/**
 * Class ServiceManager
 * Borrowed heavily from ZF2
 */
class ServiceManager implements IServiceManager {
	private $aliases = array();
	private $instances = array();
	#private $allowOverride = false;
	#private $canonicalizedNames = array();
	private $invokableClasses = array();
	private $factories = array();
	private $shared = array(); // Everything is assumed to be shared unless $shared[$name] is explicitly set to FALSE

	public function __construct(IServiceManagerConfig $config = null) {
		if ($config) {
			$config->configureServiceManager($this);
		}
	}

	public function has($name) {}

	public function get($name) {

		$use_shared = !isset($this->shared[ $name ]) || (isset($this->shared[ $name ]) && false === $this->shared[ $name ]);

		$instance = null;

		// Check if this is an alias
		if (isset($this->aliases[ $name ])) {

			$instance = $this->get($this->aliases[ $name ]);

			// Check if this instance has already been established
		} else if (isset($this->instances[ $name ]) && $use_shared) {

			$instance = $this->instances[ $name ];

		} else if (isset($this->factories[ $name ])) {

			$factory = $this->factories[ $name ];

			if (is_string($factory) && class_exists($factory, true)) {
				$factory = new $factory;
				$this->factories[ $name ] = $factory;
			}

			if ($factory instanceof IFactory) {
				$instance = $this->createServiceViaCallback(array($factory, 'createService'), $name);
			} else if (is_callable($factory)) {
				$instance = $this->createServiceViaCallback($factory, $name);
			}

		}

		if (null === $instance) {
			throw new Exception(sprintf('Unable to locate service: %s', $name));
		}

		if ($instance instanceof IServiceManagerAware) {
			$instance->setServiceManager($this);
		}
		$this->instances[ $name ] = $instance;
		return $instance;
	}
	private function createServiceViaCallback($callable, $name) {

		static $circularDependencyResolver = array();
		$depKey = spl_object_hash($this) . '-' . $name;

		if (isset($circularDependencyResolver[$depKey])) {
			$circularDependencyResolver = array();
			throw new Exception('Circular dependency was found for this instance');
		}

		try {
			$circularDependencyResolver[$depKey] = true;
			#$instance = call_user_func($callable, $this, $name);
			$instance = call_user_func($callable, $this);
			unset($circularDependencyResolver[$depKey]);
		} catch (Exception $e) {
			unset($circularDependencyResolver[$depKey]);
			throw $e;
		}

		if (null === $instance) {
			throw new Exception('The factory was called but did not return an instance');
		}

		return $instance;
	}
	/**
	 * Map an alias to a name
	 * @param string $alias The aliased by which name can be found
	 * @param string $name
	 */
	public function setAlias($alias, $name) {
		$this->aliases[$alias] = $name;
	}

	/**
	 * @param string $name
	 * @param mixed $factory
	 * @param bool $shared Whether the factory is shared
	 * @return ServiceManager
	 * @throws InvalidArgumentException
	 */
	public function setFactory($name, $factory, $shared = null) {

		if (!($factory instanceof IFactory || is_string($factory) || is_callable($factory))) {
			throw new InvalidArgumentException('Factory must implement IFactory');
		}

		$this->factories[$name] = $factory;
		$this->shared[$name] = $shared;

		return $this;
	}

	public function setShared($name, $isShared) {
		$this->shared[$name] = $isShared;
	}

	/**
	 * Register a service with the locator
	 *
	 * @param string $name
	 * @param mixed $service
	 * @return ServiceManager
	 */
	public function setService($name, $service) {
		$this->instances[$name] = $service;
		return $this;
	}
}