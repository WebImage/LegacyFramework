<?php

interface CWI_DB_ITableCreator {
	function __construct($model);
	public function getModel();
	public function tableExists($table_name=null);
	public function createTable($table_name=null);
	public function updateOrCreateTable();
}

FrameworkManager::loadLibrary('db.modelresult');
FrameworkManager::loadLibrary('db.modelfielddiff');
FrameworkManager::loadLibrary('db.modeldiff');
FrameworkManager::loadLibrary('db.tablecreatorfactory'); // Included because some internal code may assume that CWI_DB_TableCreatorFactory is still in this file... need to find and fix that code

class CWI_DB_MySqlTableCreator implements CWI_DB_ITableCreator {
	var $model;
	
	function __construct($model) {
		$this->model = $model;
	}
	public function getModel() {
		if (empty($this->model)) throw new Exception('Model not defined.');
		return $this->model;
	}
	
	/**
	 * Compares two models, the original or current model and the most recent version of the model
	 */
	private function compareModels($current_model, $target_model) {
		$model_differences = CWI_DB_ModelDiff::compareModels($current_model, $target_model);
		return $model_differences;
	}
	
	/**
	 * @return CWI_DB_ModelResult
	 **/
	public function updateOrCreateTable() {
		#if (is_null($table_key)) $table_key = $this->getModel()->getName();
		
		if ($this->tableExists($this->getModel()->getTableName())) {
			// The model as it should be
			$target_model = $this->getModel();
			
			// The model as it currently is in the database
			FrameworkManager::loadLibrary('db.databasehelper');
			$existing_model = CWI_DB_DatabaseHelper::createModelFromTableKey($this->getModel()->getName());
					
			$model_diff = CWI_DB_MySqlTableCreator::compareModels($existing_model, $target_model);
			
			$type = '';
			if (count($model_diff->getFieldChanges()) > 0) {
				$this->updateTable($model_diff);
				$type = CWI_DB_ModelResult::TYPE_UPDATED;
			} else {
				$type = CWI_DB_ModelResult::TYPE_UNCHANGED;
			}
			
			return new CWI_DB_ModelResult($target_model, $type, $model_diff);
		} else {
			return $this->createTable($this->getModel()->getTableName());
		}
	}
	
	private function getModelDefStringFromField($field) {
		$field_size = $field->getSize();
		$field_scale = $field->getScale();
		$field_default = $field->getDefault();
		
		$field_def = strtoupper($field->getType());
		
		$primary_keys = array();
		
		if (!empty($field_size)) {
			if (!is_numeric($field_size)) throw new Exception('Unable to create table: ' . $table_name . ' because `' . $field->getName() . '`\'s size was not numeric: ' . $field_size);
			$field_def .= '(';
			$field_def .= $field_size;
			if (!empty($field_scale)) {
				if (!is_numeric($field_scale)) throw new Exception('Unable to create table: ' . $table_name . ' because `' . $field->getName() . '`\ scale was not numeric: ' . $field_scale);
				if ($field_scale > $field_size) throw new Exception('Unable to create table: ' . $table_name . ' because `' . $field->getName() . '\ scale ('.$field_scale.') was larger than its size('.$field_size.').');
				$field_def .= ', ' . $field_scale;
			}
			$field_def .= ')';
		}
		
		if ($field->isRequired()) $field_def .= ' NOT NULL';
		if ($field->isAutoIncrement()) $field_def .= ' AUTO_INCREMENT';
		if (!empty($field_default)) {
			if (!is_numeric($field_default)) $field_default = "'" . $field_default . "'";
			$field_def .= ' DEFAULT ' . $field_default;
		}
		if ($field->isPrimaryKey()) array_push($primary_keys, '`' . $field->getName() . '`');
		return $field_def;
	}

	public function updateTable($model_diff) {
		$sqls = $this->updateTableFields($model_diff);

		$dao = new DataAccessObject();
		$dao->setCacheResults(false);

		foreach($sqls as $sql) {
			$dao->commandQuery($sql);
		}
	}

	/**
	 * @param $model_diff
	 *
	 * @return string[]
	 * @throws Exception
	 */
	public function updateTableFieldsSql($model_diff) {
		$dao = new DataAccessObject();
		$dao->setCacheResults(false);
		$sqls = array();

		$table_name = $model_diff->getSourceModel()->getTableName();
		
		$field_changes = $model_diff->getFieldChanges();
		
		// Current table fields
		$source_fields = $model_diff->getSourceModel()->getFields();
		
		// Store field names as stack so that we can figure out the order of the new fields to be added
		$existing_fields = array();
		foreach($source_fields as $source_field) {
			array_push($existing_fields, $source_field->getName());
		}
		
		foreach($field_changes as $field_change) {
			
			$target_field = $field_change->getTargetField();
			$target_field_name = $target_field->getName();
			
			$sql = '';
			
			switch ($field_change->getDiffType()) {
				case CWI_DB_ModelFieldDiff::STATUS_MODIFIED:
					$def = $this->getModelDefStringFromField($target_field);
					$sql = 'ALTER TABLE `' . $table_name . '` MODIFY COLUMN `' . $target_field_name . '`' . $def;
					break;
				case CWI_DB_ModelFieldDiff::STATUS_ADDED:
					
					
					// Get new field definition
					$def = $this->getModelDefStringFromField($target_field);
					
					// Temp space for sql field ordering
					$sql_add_order = '';
					
					$found = false;
					for($i=0, $j=count($existing_fields); $i < $j; $i++) {
						$existing_field = $existing_fields[$i];
						
						$cmp = strcmp($target_field_name, $existing_field);
						
						if ($i == 0 && $cmp < 0) { // If the field to be added is less than the first field, escape because this will become the new first field
							break;
						} else if ($i > 0 && $cmp < 0) {
							array_splice($existing_fields, $i, 0, array($target_field_name));
							$sql_add_order .= ' AFTER `' . $existing_fields[$i-1] . '`';
							$found = true;
							break;
						}
					}
					if (!$found) $sql_add_order = ' FIRST';
						
					$sql = 'ALTER TABLE `' . $table_name . '` ADD COLUMN `' . $target_field_name . '`' . $def . ' ' . $sql_add_order;
					break;
				case CWI_DB_ModelFieldDiff::STATUS_DELETED:
					$sql_test_values = "SELECT COUNT(*) AS records FROM `" . $table_name . "` WHERE `" . $target_field_name . "` IS NOT NULL";
					$result = $dao->selectQuery($sql_test_values)->getAt(0);
					$records_using_field = $result->records;
					
					if ($records_using_field == 0) { // Only delete a field if it has never been used
						$sql = 'ALTER TABLE `' . $table_name . '` DROP COLUMN `' . $target_field_name . '`';
					}
					break;
			}
			
			if (!empty($sql)) $sqls[] = $sql;
		}

		return $sqls;
	}
	/**
	 * @return boolean whether or not the table exists
	 */
	public function tableExists($table_name=null) {
		$model = $this->getModel();
		
		if (empty($table_name)) $table_name = $this->getModel()->getTableName();
		
		// Data Access Object
		$dao = new DataAccessObject();
		$dao->setCacheResults(false);
		return $dao->tableExists($table_name);
	}

	public function createTableSql($table_name=null) {
		$model = $this->model;

		if (empty($table_name)) $table_name = $this->getModel()->getTableName();

		$fields = $model->getFields();
		$primary_keys = array();
		$sql_fields = array();
		$sql = 'CREATE TABLE `' . $table_name . '`';

		foreach($fields as $field) {
			$field_size = $field->getSize();
			$field_scale = $field->getScale();
			$field_default = $field->getDefault();

			$field_sql = '`' . $field->getName() . '` ' . strtoupper($field->getType());

			if (!empty($field_size)) {
				if (!is_numeric($field_size)) throw new Exception('Unable to create table: ' . $table_name . ' because `' . $field->getName() . '`\'s size was not numeric: ' . $field_size);
				$field_sql .= '(';
				$field_sql .= $field_size;
				if (!empty($field_scale)) {
					if (!is_numeric($field_scale)) throw new Exception('Unable to create table: ' . $table_name . ' because `' . $field->getName() . '`\ scale was not numeric: ' . $field_scale);
					if ($field_scale > $field_size) throw new Exception('Unable to create table: ' . $table_name . ' because `' . $field->getName() . '\ scale ('.$field_scale.') was larger than its size('.$field_size.').');
					$field_sql .= ', ' . $field_scale;
				}
				$field_sql .= ')';
			}

			if ($field->isRequired()) $field_sql .= ' NOT NULL';
			if ($field->isAutoIncrement()) $field_sql .= ' AUTO_INCREMENT';
			if (null !== $field_default) {
				if (!is_numeric($field_default)) $field_default = "'" . $field_default . "'";
				$field_sql .= ' DEFAULT ' . $field_default;
			}
			if ($field->isPrimaryKey()) array_push($primary_keys, '`' . $field->getName() . '`');
			array_push($sql_fields, $field_sql);
		}
		$sql .= ' (' . implode(',', $sql_fields);
		if (count($primary_keys) > 0) $sql .= ', PRIMARY KEY (' . implode(',', $primary_keys) . ')';
		$sql .= ')';

		return $sql;
	}

	public function createTable($table_name=null) {
		$sql =$this->createTableSql($table_name);
		
		// Data Access Object
		$dao = new DataAccessObject();
		
		if (!$dao->commandQuery($sql)) {
			if ($dao->anyErrors()) {
				$errors = $dao->getErrors();
				$reason = implode('  ', $errors);
				throw new Exception('Unable to create table: ' . $table_name . '.  Reason: ' . $reason);
			} else {
				throw new Exception('Unable to create table: ' . $table_name . '.  Reason: Unknown.');
			}
		}
		
		return new CWI_DB_ModelResult($model, CWI_DB_ModelResult::TYPE_CREATED);
		#die('getCreateTableSQL()');
	}
}

?>