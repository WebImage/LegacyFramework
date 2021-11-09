<?php

namespace WebImage\Core;

use ArrayAccess, Iterator;

class Dictionary implements ArrayAccess, Iterator { // extends  IDictionary {
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
		if (is_a($dictionary_or_array, Dictionary::class)) {
			$array = $dictionary_or_array->lst;
		} else if (is_array($dictionary_or_array) || $dictionary_or_array instanceof \Traversable) {
			$array = $dictionary_or_array;
		} else {
			return false;
		}
		
		foreach($array as $key=>$value) {
			$this->set($key, $value);
		}
	}

	/**
	 * Return an associative array of the stored data.
	 *
	 * @return array
	 */
	public function toArray()
	{
		$array = array();
		$data = $this->lst;

		/** @var static $value */
		foreach ($data as $key => $value) {
			if ($value instanceof static) {
				$array[$key] = $value->toArray();
			} else {
				$array[$key] = $value;
			}
		}

		return $array;
	}

	/**
	 * Implement methods from ArrayAccess
	 **/
	public function offsetExists($key) {
		return $this->isDefined($key);
	}
	
	public function offsetGet($key) {
		return $this->get($key);
	}
	
	public function offsetSet($key, $value) {
		$this->set($key, $value);
	}
	
	public function offsetUnset($key) {
		$this->del($key);
	}
	
	/**
	 * Implements methods from Iterator
	 */
	public function current() {
		return current($this->lst);
	}
	
	public function next() {
		return next($this->lst);
	}
	
	public function key() {
		return key($this->lst);
	}
	
	public function valid() {
		$key = key($this->lst);
		
		return ($key !== null && $key !== false);
	}
	
	public function rewind() {
		return reset($this->lst);
	}
	
}
