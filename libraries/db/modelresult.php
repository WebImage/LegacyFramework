<?php

class CWI_DB_ModelResult { // Wrapper for model (whether updated or created)
	const TYPE_CREATED = 'created';
	const TYPE_UPDATED = 'updated';
	const TYPE_UNCHANGED = 'unchanged';
	
	private $model, $modelDiff, $type;
	
	function __construct($model, $type, $model_diff=null) {
		$this->model = $model;
		$this->type = $type;
		$this->modelDiff = $model_diff;
	}
	public function getModel() { return $this->model; }
	public function getModelDiff() { return $this->modelDiff; }
	public function getType() { return $this->type; }
	
}

?>