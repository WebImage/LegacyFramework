<?php

FrameworkManager::loadLibrary('db.model');
FrameworkManager::loadLibrary('db.modelfield');
FrameworkManager::loadLibrary('db.modelindex');
FrameworkManager::loadLibrary('db.modelindexfield');
FrameworkManager::loadLibrary('xml.compile');

/**
 * 01/27/2010	(Robert Jones) Modified class to take advantage of the fact that CWI_XML_Compile::compile() now throws errors
 */
class CWI_SYNC_DatabaseConversionException extends Exception {}

class CWI_DB_DatabaseHelper {
	
	public static function createModelFromTableKey($table_key) {
		$model = new CWI_DB_Model();

		$table_name = DatabaseManager::getTable($table_key);
		$model->setTableName($table_name);
		$model->setName($table_key);
		
		/*
		if ($table_key = DatabaseManager::getTableKey($table_name)) {
			$model->setName($table_key);
		} else { // Have to default to something, so we'll use the table name
			$model->setName($table_name);
		}
		*/
		
		$dao = new DataAccessObject();
		
		// Columns
		$sql_select = "SHOW COLUMNS FROM `" . $table_name . "`";
		$rows = $dao->selectQuery($sql_select);

		if ($rows->getCount() == 0) throw new Exception ('Unknown table');
		
		while ($row = $rows->getNext()) {
			$model_field = new CWI_DB_ModelField();
			$model_field->setName($row->Field);
			
			$type = strtolower($row->Type);
			
			if (preg_match('/(.+)\(([0-9]+) *,([0-9]+)\)/', $type, $matches)) { // Probably decimal
				$model_field->setType($matches[1]);
				$model_field->setSize($matches[2]);
				$model_field->setScale($matches[3]);
			} else if (preg_match('/(.+)\(([0-9]+)\)/', $type, $matches)) { // Probably int or varchar
				$type = $matches[1];
				$size = $matches[2];
				$model_field->setType($type);
				if (!in_array($type, array('int', 'tinyint'))) {
					$model_field->setSize($size);
				}
			} else {
				$model_field->setType($type);
			}
			
			if (isset($row->Null) && strtolower($row->Null) != 'yes') {
				$model_field->setRequired(true);
			}
			
			if (isset($row->Default) && !empty($row->Default)) {
				$model_field->setDefault($row->Default);
			}
			
			if (isset($row->Key) && $row->Key == 'PRI') {
				$model_field->setPrimaryKey(true);
			}
			
			if (isset($row->Extra) && !empty($row->Extra)) {
				$extra_values = explode(' ', strtolower($row->Extra));
				if (in_array('auto_increment', $extra_values)) {
					$model_field->setAutoIncrement(true);
				}
			}
			$model->addField($model_field);
		}
		
		// Indexes
		$sql_select = "SHOW INDEXES FROM `" . $table_name . "`";
		$rows = $dao->selectQuery($sql_select);
		$indexes = array();
		
		while ($row = $rows->getNext()) {
			if (!isset($indexes[$row->Key_name])) $indexes[$row->Key_name] = new CWI_DB_ModelIndex($row->Key_name);
			
			$length = (isset($row->Sub_part) && !empty($row->Sub_part)) ? $row->Sub_part : null;
			
			$index_field = new CWI_DB_ModelIndexField($row->Column_name, $length);
			$indexes[$row->Key_name]->addField($index_field);
		}
		
		foreach($indexes as $index_key=>$index) {
			$model->addIndex($index);
		}
		
		return $model;
	}

	/**
	 * @param $xml_traversal_model
	 * @return CWI_DB_Model
	 * @throws CWI_SYNC_DatabaseConversionException
	 */
	public static function convertXmlToModel($xml_traversal_model) {
		
		FrameworkManager::loadLibrary('db.model');

		if (is_string($xml_traversal_model)) {
			try {
				$xml_traversal_model = CWI_XML_Compile::compile($xml_traversal_model);
			} catch (Exception $e) {
				throw new CWI_SYNC_DatabaseConversionException('Unable to compile XML');
			}
		} else if (!
		(is_object($xml_traversal_model) && is_a($xml_traversal_model, 'CWI_XML_Traversal'))
		) {
			throw new CWI_SYNC_DatabaseConversionException('Passing unknown type (of type '.gettype($xml_traversal_model) . ') to convertXmlToModel()');
		}

		if ($xml_model_root = $xml_traversal_model->getPathSingle('/model')) {
				
			// Initiate database model
			$model = new CWI_DB_Model();
			if (!$model_name = $xml_model_root->getParam('name')) {
				$model_name = 'UNKNOWN_MODEL_NAME';
				throw new CWI_SYNC_DatabaseConversionException('Model name missing.');
			}
				
			if (!$table_name = $xml_model_root->getParam('tableName')) {
				#throw new CWI_SYNC_DatabaseConversionException('Table name missing.');
				$table_name = DatabaseManager::getTable($model_name);
			}
				
				
			// Set model version
			$model_version = $xml_model_root->getParam('version');
			if (empty($model_version)) $model_version = 1;
			$model->setVersion($model_version);
	
			// Set model name
			$model->setName($model_name);
			$model->setTableName($table_name);
				
			if ($model_instance_name = $xml_model_root->getParam('instanceName')) $model->setInstanceName($model_instance_name);
				
			/**
			 * Check internationalization parameters
			*/
			if ($xml_param_i18n = $xml_model_root->getParam('i18n')) {
				if ($xml_param_i18n == 'true') {
					$model->i18n(true);
					$model->isI18nRequired(true);
				} else if ($xml_param_i18n == 'false') {
					$model->i18n(false);
					$model->isI18nRequired(false);
				} else if ($xml_param_i18n == 'dependent') {
					if ($xml_param_i18n_dependent_on = $xml_model_root->getParam('i18nDependentOn')) {
						$model->i18n(true);
						$model->isI18nDependent(true);
						$model->isI18nRequired(false);
						$model->setI18nDependentOn($xml_param_i18n_dependent_on);
					} else {
						throw new CWI_SYNC_DatabaseConversionException('i18n is set to: dependent, but the param: i18nDependentOn is missing');
					}
				} else if ($xml_param_i18n == 'optional') {
					$model->i18n(true);
					$model->isI18nRequired(false);
				} else {
					throw new CWI_SYNC_DatabaseConversionException('Invalid i18n parameter value: ' . $xml_param_i18n . '. Expecting: true, false, or dependent');
				}
			} else {
				if ($xml_model_root->getParam('i18nDependentOn')) throw new CWI_SYNC_DatabaseConversionException('Model has param: i18nDependentOn, but is missing param: i18n.');
			}
				
			/**
			 * Retrieve required models
			 */
			/*
				$required_model_lookup = array();
			if ($xml_required_models = $xml_model_root->getPath('requirements/models/model')) {
			foreach($xml_required_models as $xml_required_model) {
			#echo 'Required Model: '. $xml_required_model->getParam('name') . ' - ' . $xml_required_model->getParam('link') . '<br />';
			try {
			$required_model_plugin = CWI_MANAGER_SyncManager::getModelPluginByLink($xml_required_model->getParam('name'), $xml_required_model->getParam('link'));
			$required_model_lookup[$xml_required_model->getParam('name')] = $required_model_plugin->getModel();
			} catch (CWI_MANAGER_SyncException $e) {
			throw $e;
			}
			}
			}
			*/
				
			if ($fields = $xml_model_root->getPath('fields/field')) {
	
				$valid_field_types = array('int', 'tinyint', 'varchar', 'datetime', 'date', 'decimal', 'text', 'char'); // boolean, smallint, integer, bigint, double, float, real, decimal, char, varchar(size), longvarchar, date, time, timestamp, bu_date, bu_timestamp, blob, and clob
	
				$related_models = array();
	
				foreach($fields as $field) {
					$model_field = new CWI_DB_ModelField();
						
					// Check that field name exists
					if (!$field_name = $field->getParam('name')) {
						$field_name = 'UNKNOWN_FIELD_NAME';
						throw new CWI_SYNC_DatabaseConversionException('Required field parameter missing: name.');
					}
					$model_field->setName($field_name);
					/*
						if ($xml_param_related_model = $field->getParam('relatedModel')) {
					if (!isset($related_models[$xml_param_related_model])) $related_models[$xml_param_related_model] = array();
					$xml_param_related_model_key = $field->getParam('relatedModelKey');
					if (empty($xml_param_related_model_key)) $xml_param_related_model_key = 'id';
					$related_models[$xml_param_related_model][$xml_param_related_model_key] = $field->getParam('name');
					}
					*/

					// If field type is not defined, check if the field is a Athena system field
	
					if (!$field_type = $field->getParam('type')) {
						switch ($field_name) {
							case 'created':
							case 'updated':
								$field_type = 'datetime';
								break;
							case 'tinyint':
							case 'enable':
								$field_type = 'tinyint';
								break;
								/**
								 * No Breaks Below
								 */
							case 'id':
							case 'created_by':
							case 'updated_by':
							case 'sortorder':
							default:
								$field_type = 'int';
								if ($field_name == 'id') {
									if (!$field->getParam('primaryKey')) {
										$field->setParam('primaryKey', 'true');
										$field->setParam('required', 'true');
									}
									if (!$field->getParam('autoIncrement')) $field->setParam('autoIncrement', 'true');
								}
								break;
						}
					}
						
					$model_field->setType($field_type);

					if (($field_required = $field->getParam('required')) !== false)		$model_field->setRequired( ($field_required == 'true') );
					if (($field_size = $field->getParam('length')) !== false)			$model_field->setSize($field_size);
					if (($field_scale = $field->getParam('scale')) !== false)			$model_field->setScale($field_scale);
					if (($field_default = $field->getParam('default')) !== false)		$model_field->setDefault($field_default);
					if (($field_primary = $field->getParam('primaryKey')) !== false)		$model_field->setPrimaryKey( ($field_primary == 'true') );
					if (($field_auto_inc = $field->getParam('autoIncrement')) !== false)	$model_field->setAutoIncrement( ($field_auto_inc == 'true') );
					
					$model->addField($model_field);
					// Check if field type is valid:
					if (!in_array($field_type, $valid_field_types)) throw new CWI_SYNC_DatabaseConversionException('The model: ' . $model_name . ' with field: ' . $field_name . ' contains an invalid field type: ' . $field_type . '.');
					if ($field_type == 'varchar' && !$field_size) throw new CWI_SYNC_DatabaseConversionException('The model: ' . $model_name . ' with field: ' . $field_name . ' is missing: length.');
				}
				/*
					foreach($related_models as $related_model_name=>$related_model_keys) {
				if (!isset($required_model_lookup[$related_model_name])) throw new CWI_SYNC_DatabaseConversionException('Model: ' . $model_name . ' is missing required related model: ' . $related_model_name . '.  More than likely this is a bad association on the sync server at: ' . ConfigurationManager::get('SYNC_SERVER') . '.');
					
				$related_model = new CWI_DB_RelatedModel($required_model_lookup[$related_model_name]);
				foreach($related_model_keys as $related_model_key=>$this_model_key) {
				$related_model->setKeyAssociation($related_model_key, $this_model_key);
				}
				#echo '<pre>';
				#print_r(get_class_methods($model));exit;
				$model->addRelatedModel($related_model);
				}
				*/
			} else {
				throw new CWI_SYNC_DatabaseConversionException('Unable to find any fields for the model: ' . $model_name);
			}
	
			return $model;
		} else {
			throw new CWI_SYNC_DatabaseConversionException('Could not find root element: model.');
		}
	}
}

?>