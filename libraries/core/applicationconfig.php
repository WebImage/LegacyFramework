<?php

class ApplicationConfig implements ArrayAccess {
	protected $data=array();
	public function __construct(array $data=array()) {
		$this->data = $data;
	}
	public function offsetExists($offset) {
		return (isset($this->data[$offset]));
	}

	public function offsetGet($offset) {
		return $this->data[$offset];
	}

	public function offsetSet($offset, $value) {
		$this->data[$offset] = $value;
	}

	public function offsetUnset($offset) {
		unsset($this->data[$offset]);
	}

	public function merge($data) {
		$this->data = array_replace_recursive($this->data, $data);
	}
}