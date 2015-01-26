<?php

/**
 * Keeps track of what needs to be done to the source model to make it like the target model
 */
class CWI_DB_ModelDiff {
	private $sourceModel, $targetModel;
	private $fields = array();
	public function getSourceModel() { return $this->sourceModel; }
	public function getTargetModel() { return $this->targetModel; }
	public function getFieldChanges() { return $this->fields; }
	
	public static function compareModels($source, $target) {
		$model_diff = new CWI_DB_ModelDiff();
		$model_diff->sourceModel = $source;
		$model_diff->targetModel = $target;
		
		$current_fields = $model_diff->sourceModel->getFields();
		$target_fields = $model_diff->targetModel->getFields();
		
		$model_diff->fields = CWI_DB_ModelFieldDiff::compareFields($current_fields, $target_fields);
		
		return $model_diff;
	}

}

?>