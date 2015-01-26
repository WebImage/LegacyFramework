<?php

class CWI_DB_RelatedModel {
	private $model; // CWI_DB_Model
	private $keyAssociations; // Dictionary
	public function __construct($model) {
		$this->model = $model;
		$this->keyAssociations = new Dictionary();
	}
	public function getModel() { return $this->model; }
	public function getKeyAssociations() { return $this->keyAssociations; }
	public function setKeyAssociation($related_model_key, $model_key) {
		$this->keyAssociations->set($related_model_key, $model_key);
	}
	
}

?>