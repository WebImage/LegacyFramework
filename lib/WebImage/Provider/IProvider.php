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
	 * @return Config
	 */
	public function getConfig();
}