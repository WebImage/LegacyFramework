<?php

class CWI_DB_ModelField {
	/**
	 * @property string $type - possible values: varchar, int, decimal, text
	 **/
	private $name, $type, $required, $size, $scale, $default, $primaryKey, $autoIncrement; // $relatedModel, $foreignReference, $isCulture
	/**
	 * Don't add a constructor with required values at this point, because there is code elsewhere that relies on just being able to set this object up manually
	 **/
	function getName() { return $this->name; }
	function getType() { return $this->type; }
	function getSize() { return $this->size; }
	function getScale() { return $this->scale; }
	function getDefault() { return $this->default; }
	
	function isRequired() { return $this->required; }
	function isPrimaryKey() { return $this->primaryKey; }
	function isAutoIncrement() { return $this->autoIncrement; }
	
	function setName($name) { $this->name = $name; }
	function setType($type) { $this->type = $type; }
	function setRequired($required) { $this->required = $required; }
	function setSize($size) { $this->size = $size; }
	function setScale($scale) { $this->scale = $scale; }
	function setDefault($default) { $this->default = $default; }
	function setPrimaryKey($primary_key) { $this->primaryKey = $primary_key; }
	function setAutoIncrement($auto_increment) { $this->autoIncrement = $auto_increment; }
}

?>