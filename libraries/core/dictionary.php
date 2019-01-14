<?php

class Dictionary implements Countable, Iterator, ArrayAccess { // extends  IDictionary {
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
	function get($key) {
		if ($this->isDefined($key)) return $this->lst[$key];
		else return false;
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
	/** Implement methods from ArrayAccess */
	public function offsetExists($key) { return $this->isDefined($key); }
	public function offsetGet($key) { return $this->get($key); }
	public function offsetSet($key, $value) { $this->set($key, $value); }
	public function offsetUnset($key) { $this->del($key); }
	/** Countable */
	public function count() { return count($this->lst); }
	/** Iterable */
	public function current() { return current($this->lst); }
	public function next() { return next($this->lst); }
	public function key() { return key($this->lst); }
	public function valid() { return ($this->key() !== null); }
	public function rewind() { reset($this->lst); }
	
	/**
	 * Get the defined keys
	 *
	 * @return array
	 */
	public function keys() { return array_keys($this->lst); }
	
	/**
	 * Return an associative array of the stored data.
	 *
	 * @return array
	 */
	public function toArray() {
		$array = array();
		
		/** @var static $value */
		foreach ($this->lst as $key => $value) {
			if ($value instanceof static) {
				$array[$key] = $value->toArray();
			} else {
				$array[$key] = $value;
			}
		}
		
		return $array;
	}
}
