<?php

class CWI_DB_ModelIndex {
	private $name, $fields = array();
	//private $type; // Not yet used..
	
	public function __construct($name) {
		$this->setName($name);
	}
	
	public function getName() { return $this->name; }
	public function getFields() { return $this->fields; }
	
	public function setName($name) { $this->name = $name; }
	public function addField(CWI_DB_ModelIndexField $model_index_field) {
		array_push($this->fields, $model_index_field);
	}
}

?>