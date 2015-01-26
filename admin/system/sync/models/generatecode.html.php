<?php
#FrameworkManager::loadLibrary('xml.compile');
FrameworkManager::loadManager('sync');
FrameworkManager::loadLibrary('sync.databasehelper');
#FrameworkManager::loadLibrary('db.generator');
#FrameworkManager::loadLibrary('db.model');
#class StructGenerator {}
#class DAOGenerator {}
#class LogicGenerator {}

$model		= Page::get('model');
$retrieval_link	= Page::get('retrievallink');
if (Page::get('nocache')) {
	$cache_timeout = 1;
} else {
	$cache_timeout = 24 * 60 * 60; // 24 hours
}
function getObjectName($underscored) {
	$words = explode('_', $underscored);
	$object_name = '';

	foreach($words as $word) {
		$object_name .= strtoupper(substr($word, 0, 1)) . substr($word, 1);
	}
	
	if (substr($object_name, -3) == 'ies') $object_name = substr($object_name, 0, -3) . 'y'; // Change plural "ies" to singular "y"
	else if (substr($object_name, -1) == 's') $object_name = substr($object_name, 0, -1); // Change plural to singular
	
	return $object_name;
}

$errors = array(); // Keep track of errors
$path = ConfigurationManager::get('DIR_FS_FRAMEWORK_APP') . 'install/models/';

if (!$model = Page::get('model')) die("MISSING MODEL");
	


#$xml = file_get_contents($path.$model.'.xml');
/*
$xml_response = new CWI_XML_Traversal('response');
$xml_valid = true;
try {
	$xml_model = CWI_XML_Compile::compile($xml);
} catch (CWI_XML_CompileException $e) {
	$xml_valid = false;
}

if ($xml_valid) {
	$xml_response->setParam('status', 'success');
	$xml_response->addChild($xml_model);
} else {
	$xml_response->setParam('status', 'failed');
	$xml_error = new CWI_XML_Traversal('error', 'There was an internal problem retrieval the ' . $model . ' model.');
	$xml_response->addChild($xml_error);
}
*/
try {
	if (!$model_plugin = CWI_MANAGER_SyncManager::getModelPluginByLink($model, $retrieval_link, $cache_timeout)) {
		ErrorManager::addError('Sync manager was unable to load an xml model for: ' . $model);
	}
} catch (CWI_MANAGER_SyncException $e) {
	ErrorManager::addError('There was a problem with the request: ' . $e->getMessage());
}
/*
if (!ErrorManager::anyDisplayErrors()) {
	try {
		$model = CWI_SYNC_DatabaseHelper::convertXmlToModel( $model_plugin->getModel() );
	} catch (Exception $e) {
		ErrorManager::addError('Database helper error: ' . $e->getMessage());
	}
}
*/
$model = $model_plugin->getModel();

if (!ErrorManager::anyDisplayErrors()) {
	$generated_output = Page::getControlById('generated_output');

	$output = '<pre>';
	// Configuration Table [Add]
	$output .= htmlentities('<add name="'.$model->getName() . '" value="' . $model->getName() . '" />') . "\r\n";

	$object_name = getObjectName($model->getInstanceName());

	$fields_array = array();
	$fields = $model->getFields();
	$primary_keys = array();
	foreach($fields as $field) {
		if ($field->isPrimaryKey()) {
			$primary_keys[] = $field->getName();
		} else {
			$fields_array[] = $field->getName();
		}
	}

	#$fields_array = fields2Array($update_fields);	
	// Struct
	$struct_fields = array();
	#foreach($fields_array as $f) array_push($struct_fields, $f);
	#if (!empty($primary_key)) array_push($struct_fields, $primary_key);
	
	foreach($model->getFields() as $field) {
		$struct_fields[] = $field->getName();
	}
	#$struct_fields = $model->getFields();
	
	sort($struct_fields);
	$output .= "\r\n";
	$output .= strtolower($object_name) . '_structure.php' . "\r\n";
	$output .= '&lt;?php' . "\r\n";
	$output .= "\r\n";
	$output .= '/**' . "\r\n";
	$output .= ' * Data structure for ' . getObjectName($model->getName()) . "\r\n";
	$output .= ' * ' . "\r\n";
	$output .= ' * @author Robert Jones II <support@corporatewebimage.com>' . "\r\n";
	$output .= ' * @copyright Copyright (c) 2007 Corporate Web Image, Inc.' . "\r\n";
	$output .= ' * @package DataAccessObject' . "\r\n";
	$output .= ' * @version 1.0 ('.date('m/d/Y')  .'), Athena_v1.0' . "\r\n";
	$output .= ' */' . "\r\n";
	$output .= "\r\n";
	$output .= 'class ' . $object_name . 'Struct {' . "\r\n";
	$output .= '	var $' . implode(', $', $struct_fields) . ';' . "\r\n";
	$output .= '}' . "\r\n";
	$output .= "\r\n";
	$output .= '?&gt;' . "\r\n";
	
	
	// DAO
	$output .= "\r\n";
	$output .= strtolower($object_name) . '_dao.php' . "\r\n";
	$output .= '&lt;?php' . "\r\n";
	$output .= "\r\n";
	$output .= '/**' . "\r\n";
	$output .= ' * DataAccessObject for ' . getObjectName($model->getName()) . "\r\n";
	$output .= ' * ' . "\r\n";
	$output .= ' * @author Robert Jones II <support@corporatewebimage.com>' . "\r\n";
	$output .= ' * @copyright Copyright (c) 2007 Corporate Web Image, Inc.' . "\r\n";
	$output .= ' * @package DataAccessObject' . "\r\n";
	$output .= ' * @version 1.0 ('.date('m/d/Y').'), Athena_v1.0' . "\r\n";
	$output .= ' */' . "\r\n";
	$output .= "\r\n";
	$output .= '// Load required data structures' . "\r\n";
	$output .= 'FrameworkManager::loadStruct(\'' . strtolower($object_name) . '\');' . "\r\n";
	$output .= "\r\n";
	$output .= 'class ' . $object_name . 'DAO extends DataAccessObject {' . "\r\n";
	$output .= '	var $modelName = \'' . $object_name . 'Struct\';' . "\r\n";
	$output .= '	var $updateFields = array(\'' . implode("','", $fields_array) . '\');' . "\r\n";
	
	if (count($primary_keys) > 0) {
		if (count($primary_keys) == 1 && $primary_keys[0] != 'id') {
			$output .= '	var $primaryKey = \'' . $primary_keys[0] . '\';' . "\r\n";
		} else if (count($primary_keys) > 1) {
			$output .= '	var $primaryKey = array(\'' . implode("', '", $primary_keys) . '\');' . "\r\n";
		}
	}
	
	$output .= '	function ' . $object_name . 'DAO() {' . "\r\n";
	$output .= '		$this->tableName = DatabaseManager::getTable(\'' . $model->getName() . '\');' . "\r\n";
	$output .= '	}' . "\r\n";
	$output .= "}\r\n";
	$output .= "\r\n";
	$output .= '?&gt;' . "\r\n";
	
	
	// Logic
	$output .= "\r\n";
	$output .= strtolower($object_name) . '.php' . "\r\n";
	$output .= '&lt;?php' . "\r\n";
	$output .= "\r\n";
	$output .= '/**' . "\r\n";
	$output .= ' * Logic for ' . $object_name . "\r\n";
	$output .= ' * ' . "\r\n";
	$output .= ' * @author Robert Jones II <support@corporatewebimage.com>' . "\r\n";
	$output .= ' * @copyright Copyright (c) 2007 Corporate Web Image, Inc.' . "\r\n";
	$output .= ' * @package Logic' . "\r\n";
	$output .= ' * @version 1.0 ('.date('m/d/Y').'), Athena_v1.0' . "\r\n";
	$output .= ' */' . "\r\n";
	$output .= "\r\n";
	$output .= '// Load required data access object' . "\r\n";
	$output .= 'FrameworkManager::loadDAO(\'' . strtolower($object_name) . '\');' . "\r\n";
	$output .= "\r\n";
	$output .= 'class ' . $object_name . 'Logic {' . "\r\n";
	$output .= '	function getAll' . getObjectName($model->getName()) . '() {' . "\r\n";
	$output .= '		$' . $model->getInstanceName() . '_dao = new ' . $object_name . 'DAO();' . "\r\n";
	$output .= '		return $' . $model->getInstanceName() . '_dao->loadAll();' . "\r\n";
	$output .= '	}' . "\r\n";
	
	$camel_primary_key = array();
	foreach($primary_keys as $primary_key) {
		$camel_primary_key[] = getObjectName($primary_key);
	}

	if (count($primary_keys) > 0) {
		$output .= '	function get' . $object_name . 'By' . implode('And', $camel_primary_key) . '($'.implode(', $', $primary_keys) .') {' . "\r\n";
		$output .= '		$' . $model->getInstanceName() . '_dao = new ' . $object_name . 'DAO();' . "\r\n";
		$output .= '		return $' . $model->getInstanceName() . '_dao->load($'.$primary_key.');' . "\r\n";
		$output .= '	}' . "\r\n";
		#$output .= '	function save' . $object_name . '($'.$model->getInstanceName().'_struct) {' . "\r\n";
		$output .= '	function save($'.$model->getInstanceName().'_struct) {' . "\r\n";
		$output .= '		$' . $model->getInstanceName() . '_dao = new ' . $object_name . 'DAO();' . "\r\n";
		$output .= '		return $' . $model->getInstanceName() . '_dao->save($' . $model->getInstanceName() . '_struct);' . "\r\n";
		$output .= '	}' . "\r\n";
		$output .= '}' . "\r\n";
		$output .= "\r\n";
	}
	$output .= '?&gt;' . "\r\n";
	
	$generated_output->setText($output);

#	echo '<pre>';
#	print_r($model);
#	echo '</pre>';
}


?>