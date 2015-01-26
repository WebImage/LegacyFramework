<?php

class Singleton {
	private static $instances = array();
	
	/**
	 * Removes all instatiated singletons
	 **/
	public static function reset() {
		self::$instances = array();
	}
	
	public static function getInstance($class) {
		if (!isset(self::$instances[$class])) {
			self::$instances[$class] = new $class;
		}
		return self::$instances[$class];
	}
	
}
