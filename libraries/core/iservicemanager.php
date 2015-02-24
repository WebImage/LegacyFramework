<?php

interface IServiceManager {
	/**
	 * Whether the named service exists
	 * @param string $name
	 * @return bool
	 */
	public function has($name);

	/**
	 * Get the named service
	 * @param string $name
	 * @return mixed
	 */
	public function get($name);
}