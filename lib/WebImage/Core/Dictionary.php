<?php

namespace WebImage\Core;

use ArrayAccess;

class Dictionary implements ArrayAccess { // extends  IDictionary {
	protected $lst = array();
	protected function getStorage() { return $this->lst; }
	function __construct($init_array=null) { // Must be an associative array
		$this->mergeDictionary($init_array);
	}
	
	function isDefined($key) {
		return isset($this->lst[$key]);
	}
	function set($key, $value) {
		$this->lst[$key] = $value;
	}
	function get($key, $default=null) {
		if ($this->isDefined($key)) return $this->lst[$key];
		else return $default;
	}
	function del($key) {
		unset($this->lst[$key]);
	}
	function getAll() {
		$dictionary_fields = new DictionaryFieldCollection();
		foreach($this->lst as $key=>$definition) {
			$dictionary_field = new DictionaryField($key, $definition);
			$dictionary_fields->add($dictionary_field);
		}
		return $dictionary_fields;
	}
	function mergeDictionary($dictionary_or_array) {
		if (is_a($dictionary_or_array, 'Dictionary')) {
			$array = $dictionary_or_array->lst;
		} else if (is_array($dictionary_or_array)) {
			$array = $dictionary_or_array;
		} else {
			return false;
		}
		
		foreach($array as $key=>$value) {
			$this->set($key, $value);
		}
	}
	/**
	 * Implement methods from ArrayAccess
	 **/
	public function offsetExists($key) { return $this->isDefined($key); }
	public function offsetGet($key) { return $this->get($key); }
	public function offsetSet($key, $value) { $this->set($key, $value); }
	public function offsetUnset($key) { $this->del($key); }
}
