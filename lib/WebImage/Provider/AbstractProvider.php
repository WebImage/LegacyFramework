<?php

namespace WebImage\Provider;

use WebImage\Core\Dictionary;

abstract class AbstractProvider implements IProvider {
	private $name; // String
	private $config; // ProviderDictionary

	/**
	 * @param $name
	 * @param Config $config
	 * @return bool
	 */
	public function init($name, Config $config=null) {
		$this->name = $name;
		if (null === $config) $config = new Dictionary();
		$this->config = $config;
		return true;
	}


	/**
	 * @return string
	 */
	public function getName() { return $this->name; }

	/**
	 * @param string $name
	 * @return void
	 */
	public function setName($name) { $this->name = $name; }

	/**
	 * @param string $name
	 * @return mixed
	 */
	public function getConfigValue($name) { return $this->config->get($name); }

	/**
	 * @param $name
	 * @param $value
	 * @return void
	 */
	public function setConfigValue($name, $value) { $this->config->set($name, $value); }
}