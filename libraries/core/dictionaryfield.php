<?php
/**
 * 02/17/2010	(Robert Jones) Added DictionaryField and DictionaryFieldCollection classes so that Dictionary can take advantage of getAll() and return useful objects
 **/
class DictionaryField {
	private $key, $definition;
	public function __construct($key, $definition) {
		$this->key = $key;
		$this->definition = $definition;
	}
	public function getKey() { return $this->key; }
	public function getDefinition() { return $this->definition; }
	// Alias for getDefinition
	public function getDef() { return $this->getDefinition(); }
}
