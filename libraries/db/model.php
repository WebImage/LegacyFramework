<?php

FrameworkManager::loadBaseLibrary('db.modelfield');
FrameworkManager::loadBaseLibrary('db.relatedmodel');

class CWI_DB_Model {
	private $name, $tableName, $instanceName, $fields = array(), $indexes = array(), $version;
	private $relatedModels = array();
	private $i18n = false, $i18nRequired = false, $i18nDependent = false, $i18nDependentOn;

	public function getName() { return $this->name; }
	public function getTableName() {
		if (empty($this->tableName)) return DatabaseManager::getTable($this->getName());
		else return $this->tableName;
	}
	public function getInstanceName() {
		if (empty($this->instanceName)) return $this->getName();
		return $this->instanceName;
	}
	public function getFields() { return $this->fields; }
	public function getIndexes() { return $this->indexes; }
	public function getVersion() { return $this->version; }
	
	// Getter/Setter
	public function i18n($true_false=null) { 
		if (is_null($true_false)) return $this->i18n;
		else $this->i18n = $true_false;
	}
	
	// Getter/Setter
	public function isI18nRequired($true_false=null) {
		if (is_null($true_false)) return $this->i18nRequired;
		else $this->i18nRequired = $true_false;
	}
	
	public function isI18nDependent($true_false) {
		if (is_null($true_false)) return $this->i18nDependent;
		else $this->i18nDependent = $true_false;
	}
	
	public function getI18nDependentOn() {
		if (!$this->i18n()) return false;
		return $this->i18nDependentOn;
	}
	
	public function setName($name) { $this->name = $name; }
	public function setTableName($table_name) { $this->tableName = $table_name; }
	public function setInstanceName($instance_name) { $this->instanceName = $instance_name; }
	public function addField($model_field) { array_push($this->fields, $model_field); }
	public function addIndex($index) { array_push($this->indexes, $index); }
	public function setVersion($version) { $this->version = $version; }
	public function setI18nDependentOn($dependent_on) { $this->i18nDependentOn = $dependent_on; }
	
	public function addRelatedModel($cwi_db_relatedmodel) {
		array_push($this->relatedModels, $cwi_db_relatedmodel);
	}
}

?>