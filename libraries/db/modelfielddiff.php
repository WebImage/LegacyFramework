<?php

class CWI_DB_ModelFieldDiff {
	private $diffType, $differences = array();
	private $targetModelField, $currentModelField;
	
	const STATUS_ADDED = 'added';
	const STATUS_MODIFIED = 'modified';
	const STATUS_DELETED = 'deleted';
	
	const FIELD_DIFF_TYPE = 'type';
	const FIELD_DIFF_REQUIRED = 'required';
	const FIELD_DIFF_SIZE = 'size';
	const FIELD_DIFF_SCALE = 'scale';
	const FIELD_DIFF_DEFAULT = 'default';
	//const FIELD_DIFF_PRIMARY = 'primary'; // Not supported
	// const FIELD_DIFF_AUTO_INCREMENT = 'auto-increment'; // Not supported
	
	function __construct($target_field, $source_field, $diff_type, $differences=array()) {
		$this->targetModelField = $target_field;
		$this->sourceModelField = $source_field;
		$this->diffType = $diff_type;
		$this->differences = array();
	}
	
	public function getTargetField() { return $this->targetModelField; }
	public function getSourceField() { return $this->sourceModelField; }
	public function getDiffType() { return $this->diffType; }
	
	/**
	 * Takes a source field array and a target field array and finds out what has to be done to the source to make it like the target
	 * @return array of CWI_DB_ModelFieldDiff
	 */
	public static function compareFields($source_fields, $target_fields) {
		$changes = array();
		
		foreach($target_fields as $target_field) {
			$has_field = false;
			
			foreach($source_fields as $source_field) {
				
				if ($source_field->getName() == $target_field->getName()) {
					$differences = array();
					if ($source_field->getType() != $target_field->getType()) {
						array_push($differences, CWI_DB_ModelFieldDiff::FIELD_DIFF_TYPE);
					}
					
					if ($source_field->getSize() != $target_field->getSize()) {
						array_push($differences, CWI_DB_ModelFieldDiff::FIELD_DIFF_SIZE);
					}
					
					if (count($differences) > 0) {
						array_push($changes, new CWI_DB_ModelFieldDiff($target_field, $source_field, CWI_DB_ModelFieldDiff::STATUS_MODIFIED, $differences));
					}
					$has_field = true;
					break;
				}
			}
			if (!$has_field) {
				array_push($changes, new CWI_DB_ModelFieldDiff($target_field, null, CWI_DB_ModelFieldDiff::STATUS_ADDED));
			}
		}
		
		foreach($source_fields as $source_field) {
			$has_field = false;
			foreach($target_fields as $target_field) {
				if ($target_field->getName() == $source_field->getName()) {
					$has_field = true;
					break;
				}
			}
			if (!$has_field) {
				array_push($changes, new CWI_DB_ModelFieldDiff($source_field, null, CWI_DB_ModelFieldDiff::STATUS_DELETED));
			}
		}
		return $changes;
	}
}

?>