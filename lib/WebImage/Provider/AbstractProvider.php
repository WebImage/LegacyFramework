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
		
		if (null === $config) {
			$config = new Config($name, __CLASS__);
		}
		
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
	 * @return Config
	 */
	public function getConfig() { return $this->config; }
}