<?php

namespace WebImage\Provider;

use WebImage\Config\LegacyConfig as BaseConfig;

/**
 * Class Config
 * @package WebImage\Provider
 */
class Config {

	/**
	 * @var string
	 */
	private $name;
	/**
	 * @var string
	 */
	private $className;
	/**
	 * @var string
	 */
	private $classFile;
	/**
	 * @var Config
	 */
	private $meta;

	function __construct($name, $class_name, $class_file=null, BaseConfig $meta=null) {
		if (empty($name)) throw new \RuntimeException('$name is a required parameter');
		if (empty($class_name)) throw new \RuntimeException('$class_name is a required parameter');
		if (null === $meta) $meta = new BaseConfig(array());
		$this->name = $name;
		$this->className = $class_name;
		$this->classFile = $class_file;
		$this->meta = $meta;
	}

	/**
	 * @return string
	 */
	public function getName() { return $this->name; }

	/**
	 * @return string
	 */
	public function getClassName() { return $this->className; }

	/**
	 * @return string
	 */
	public function getClassFile() { return $this->classFile; }

	/**
	 * @param $name
	 * @return mixed (probably string)
	 */
	public function getMetaValue($name, $default=null) { return $this->meta->get($name, $default); }

	public function isMetaValueTrue($name, $default = null) {
		if (null !== $default && !is_bool($default)) {
			throw new \RuntimeException(sprintf('%s requires bool value for $default', __METHOD__));
		}

		$value = $this->getMetaValue($name, $default);

		return ($value === true || $value == 'true' || $value == 1);
	}

	public function isMetaValueFalse($name, $default = null) {
		return !$this->isMetaValueTrue($name, $default);
	}
}
