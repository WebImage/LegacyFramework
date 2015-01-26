<?php
/**
 * 01/27/2010	(Robert Jones) Modified class to take advantage of the fact that CWI_XML_Compile::compile() now throws errors
 */
class CWI_SYNC_DatabaseConversionException extends Exception {}
class CWI_SYNC_DatabaseHelper {
	/**
	 * Convert XML to a database model that can be used to generate SQL
	 */
	public static function convertXmlToModel($xml_traversal_model) {
		#FrameworkManager::loadLibrary('db.generator');
		FrameworkManager::loadLibrary('db.model');

		if (is_string($xml_traversal_model)) {
			try {
				$xml_traversal_model = CWI_XML_Compile::compile($xml_traversal);
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
		
					if ($field_required	= $field->getParam('required'))		$model_field->setRequired( ($field_required == 'true') );
					if ($field_size		= $field->getParam('length'))		$model_field->setSize($field_size);
					if ($field_scale	= $field->getParam('scale'))		$model_field->setScale($field_scale);
					if ($field_default	= $field->getParam('default'))		$model_field->setDefault($field_default);
					if ($field_primary	= $field->getParam('primaryKey'))	$model_field->setPrimaryKey( ($field_primary == 'true') );
					if ($field_auto_inc	= $field->getParam('autoIncrement'))	$model_field->setAutoIncrement( ($field_auto_inc == 'true') );

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
	
	/**
	 * Generate a list of sub model dependencies, primarily by checking for the relatedModel attribute of <field relatedModel="..." /> tags
	 * @param Model $model The model to be checked
	 */
	 function getModelDependencies($model) {
	 }
}

?>