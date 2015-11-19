<?php

namespace WebImage\Provider;

use WebImage\Config\Config as BaseConfig;

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

	function __construct($name, $class_name, $class_file, BaseConfig $meta) {
		if (empty($name)) throw new \RuntimeException('$name is a required parameter');
		if (empty($class_name)) throw new \RuntimeException('$name is a required parameter');
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
	public function getMetaValue($name) { return $this->meta->get($name); }

}