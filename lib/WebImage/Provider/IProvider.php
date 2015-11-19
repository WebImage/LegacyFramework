<?php

namespace WebImage\Provider;

interface IProvider {
	/**
	 * To be called after constructor to setup provider
	 * @param $name
	 * @param Config $config
	 * @return mixed
	 */
	public function init($name, Config $config=null);

	/**
	 * @return string
	 */
	public function getName();

	/**
	 * @param string $name
	 * @return void
	 */
	public function setName($name);

	/**
	 * @param string $name
	 * @return mixed
	 */
	public function getConfigValue($name);

	/**
	 * @param $name
	 * @param $value
	 * @return void
	 */
	public function setConfigValue($name, $value);
}