<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

FrameworkManager::loadLibrary('compilers');
FrameworkManager::loadLibrary('xml.compile');
FrameworkManager::loadLibrary('db.databasehelper');
FrameworkManager::loadControl('input');

$xml_raw = Page::get('xml_raw');
?>
<form method="post">
<?php
if (empty($xml_raw)) {
	?>
	<textarea name="xml_raw" style="width:500px;height:250px;"><?php echo htmlentities($xml_raw); ?></textarea><br />
	<input type="submit" value="Submit" />
	<?php
} else {
	?>
	<a href="adminpages.html">Reset</a>
	<input type="hidden" name="xml_raw" value="<?php echo htmlentities($xml_raw); ?>" />
	<?php
}

if (empty($xml_raw)) exit;

/*$xml = CWI_XML_Compile::compile('<?xml version="1.0" encoding="utf-8"?>
<model name="order_status_desc">
	<fields>
		<field name="cancelled" type="tinyint" required="true" />
		<field name="enable" type="tinyint" required="true" default="1" />
		<field name="id" />
		<field name="name" type="varchar" length="255" />
		<field name="order_visible" type="tinyint" required="true" default="1" />
		<field name="shipped" type="tinyint" required="true" default="0" />
		<field name="sortorder" />
	</fields>
</model>');*/

$xml = CWI_XML_Compile::compile($xml_raw);

$page_template = '<@Page templateId="admin-default">

<cms:Content wrapClassId="ph_admin_main">
	%s
</cms:Content>';

$panel_template = '<cms:WrapOutput wrapClassId="panel">
	%s
	<cms:WrapOutput wrapClassId="panel-header">
		<cms:WrapOutput wrapClassId="panel-title">%s</cms:WrapOutput>
	</cms:WrapOutput>
	<cms:WrapOutput wrapClassId="panel-body%s">
		%s
	</cms:WrapOutput>
	%s
</cms:WrapOutput>';

$panel_save_bar = '<cms:WrapOutput wrapClassId="panel-actionbar-bottom">
		<input type="submit" value="Save" />
	</cms:WrapOutput>';

$model = CWI_DB_DatabaseHelper::convertXmlToModel($xml);

function flat_to_camel($name) {
	$words = explode('_', $name);
	$output = '';
	foreach($words as $word) {
		$output .= strtoupper(substr($word, 0, 1)) . substr($word, 1);
	}
	return $output;
}
function flat_to_words($name) {
	$words = explode('_', $name);
	
	for($i=0; $i < count($words); $i++) {
		$words[$i] = strtoupper(substr($words[$i], 0, 1)) . substr($words[$i], 1);
	}

	return implode(' ', $words);
}
function strip_plural($txt) {
	if (substr($txt, -2, 2) == 'es') $txt = substr($txt, 0, -2);
	else if (substr($txt, -1, 1) == 's') $txt = substr($txt, 0, -1);
	return $txt;
}
$default_struct_class_name = flat_to_camel(strip_plural($model->getName())) . 'Struct';
$default_struct_key = strtolower(flat_to_camel(strip_plural($model->getName())));
$default_logic_name = flat_to_camel(strip_plural($model->getName())) . 'Logic';
$default_var_name = strip_plural($model->getName());
$default_dao_class_name = flat_to_camel(strip_plural($model->getName())) . 'DAO';

$fields = $model->getFields();

?>

<table border="1">
	<tr>
		<th>Field</th>
		<th>Link</th>
		<th>Required</th>
	</tr>
<?php
$primary_keys = array();
$struct_fields = array();
$dao_update_fields = array();

foreach($fields as $field) {
	?>
	<tr>
		<td><?php echo $field->getName(); ?></td>
		<td><input type="checkbox" name="linked_fields[]" value="<?php echo $field->getName(); ?>" <?php if (in_array($field->getName(), Page::get('linked_fields', array()))) echo ' checked="true"'; ?>/></td>
		<td><input type="checkbox" name="required_fields[]" value="<?php echo $field->getName(); ?>" <?php if (in_array($field->getName(), Page::get('required_fields', array()))) echo ' checked="true"'; ?>/></td>
	</tr>
	<?php
	if ($field->isPrimaryKey()) {
		array_push($primary_keys, $field->getName());
	} else {
		$dao_update_fields[] = $field->getName();
	}
	$struct_fields[] = $field->getName();
	
}

$struct_class_name_input = new InputControl(array('type'=>'text', 'id'=>'struct_class_name'));
$struct_class_name_input->setValue($default_struct_class_name);

$struct_key_input = new InputControl(array('type'=>'text', 'id'=>'struct_key'));
$struct_key_input->setValue($default_struct_key);

$logic_name_input = new InputControl(array('type'=>'text', 'id'=>'logic_name'));
$logic_name_input->setValue($default_logic_name);

$var_name_input = new InputControl(array('type'=>'text', 'id'=>'var_name'));
$var_name_input->setValue($default_var_name);

$dao_class_name_input = new InputControl(array('type'=>'text', 'id'=>'dao_class_name'));
$dao_class_name_input->setValue($default_dao_class_name);

$plugin_name_input = new InputControl(array('type'=>'text', 'id'=>'plugin_name'));

$struct_class_name	= Page::get('struct_class_name', $default_struct_class_name);
$struct_key		= Page::get('struct_key', $default_struct_key);
$logic_name		= Page::get('logic_name', $default_logic_name);
$var_name		= Page::get('var_name', $default_var_name);
$dao_class_name		= Page::get('dao_class_name', $default_dao_class_name);
$object_name		= flat_to_camel(strip_plural($model->getName()));
$object_name_plural	= flat_to_camel($model->getName());
$friendly_name_plural	= flat_to_words($model->getName());

$plugin_name		= Page::get('plugin_name');

$prefixed_framework_load = empty($plugin_name) ? '' : $plugin_name . '/';

?>
	<tr><td colspan="2" bgcolor="#000000">&nbsp;</td></tr>
	<tr>
		<td>Struct Name: </td>
		<td colspan="2"><?php echo $struct_class_name_input->render(); ?></td>
	</tr>
	<tr>
		<td>Struct Key:</td>
		<td colspan="2"><?php echo $struct_key_input->render(); ?></td>
	</tr>
	<tr>
		<td>Logic Name:</td>
		<td colspan="2"><?php echo $logic_name_input->render(); ?></td>
	</tr>
	<tr>
		<td>Var Name:</td>
		<td colspan="2"><?php echo $var_name_input->render(); ?></td>
	</tr>
	<tr>
		<td>DAO Class Name:</td>
		<td colspan="2"><?php echo $dao_class_name_input->render(); ?></td>
	</tr>
	<tr>
		<td>Plugin Name:</td>
		<td colspan="2"><?php echo $plugin_name_input->render(); ?></td>
	</tr>
</table>

<input type="submit" value="Run" />
</form>
<?php
/*
    [0] => CWI_DB_ModelField Object
        (
            [name:CWI_DB_ModelField:private] => created
            [type:CWI_DB_ModelField:private] => datetime
            [required:CWI_DB_ModelField:private] => 
            [size:CWI_DB_ModelField:private] => 
            [scale:CWI_DB_ModelField:private] => 
            [default:CWI_DB_ModelField:private] => 
            [primaryKey:CWI_DB_ModelField:private] => 
            [autoIncrement:CWI_DB_ModelField:private] => 
        )
*/

$output = '';

function addLine(&$output, $indent=0, $line='') {
	$output .= str_repeat("\t", $indent) . htmlentities($line) . "\n";
}
/**
 * Generate list/index view
 **/

// Struct
addLine($output);
addLine($output, 0, '<!-- Structure -->');
addLine($output);
addLine($output, 0, $struct_key . '_structure.php');
addLine($output, 0, '<?php');
addLine($output);
addLine($output, 0, '/**');
addLine($output, 0, ' * Data structure for ' . $struct_class_name);
addLine($output, 0, ' * ');
addLine($output, 0, ' * @author Robert Jones II <support@corporatewebimage.com>');
addLine($output, 0, ' * @copyright Copyright (c) 2007 Corporate Web Image, Inc.');
addLine($output, 0, ' * @package DataAccessObject');
addLine($output, 0, ' * @version 1.0 ('.date('m/d/Y')  .'), Athena_v1.0');
addLine($output, 0, ' */');
addLine($output);
addLine($output, 0, 'class ' . $struct_class_name . ' {');
addLine($output, 1, 'var $' . implode(', $', $struct_fields) . ';');
addLine($output, 0, '}');
addLine($output);
addLine($output, 0, '?>');

addLine($output);

// DAO
addLine($output);
addLine($output, 0, '<!-- Data Access Object -->');
addLine($output);
addLine($output, 0, $struct_key . '_dao.php');
addLine($output, 0, '<?php');
addLine($output);
addLine($output, 0, '/**');
addLine($output, 0, ' * DataAccessObject for ' . $dao_class_name);
addLine($output, 0, ' * ');
addLine($output, 0, ' * @author Robert Jones II <support@corporatewebimage.com>');
addLine($output, 0, ' * @copyright Copyright (c) 2007 Corporate Web Image, Inc.');
addLine($output, 0, ' * @package DataAccessObject');
addLine($output, 0, ' * @version 1.0 ('.date('m/d/Y').'), Athena_v1.0');
addLine($output, 0, ' */');
addLine($output);
addLine($output, 0, '// Load required data structures');
addLine($output, 0, 'FrameworkManager::loadStruct(\'' . $prefixed_framework_load . $struct_key . '\');');
addLine($output);
addLine($output, 0, 'class ' . $dao_class_name . ' extends DataAccessObject {');
addLine($output, 1, 'var $modelName = \'' . $struct_class_name . '\';');
addLine($output, 1, 'var $updateFields = array(\'' . implode("','", $dao_update_fields) . '\');');

/*
$primary_key = trim($primary_key);
$primary_keys = array('id');

if (!empty($primary_key) && $primary_key != 'id') {
	$primary_keys = explode(',', $primary_key);
	if (count($primary_keys) == 1) {
		addLine($output, 1, 'var $primaryKey = \'' . $primary_key . '\';');
	} else if (count($primary_keys) > 1) {
		addLine($output, 1, 'var $primaryKey = array(\'' . implode("', ", $primary_keys) . '\');');
	}
}
*/
if (count($primary_keys) == 1) {
	addLine($output, 1, 'var $primaryKey = \'' . $primary_keys[0] . '\';');
} else if (count($primary_keys) > 1) {
	addLine($output, 1, 'var $primaryKey = array(\'' . implode("', ", $primary_keys) . '\');');
}

addLine($output, 1, 'public function __construct() {');
addLine($output, 2, '$this->tableName = DatabaseManager::getTable(\'' . $model->getName() . '\');');
addLine($output, 1, '}');
addLine($output, 0, "}");
addLine($output);
addLine($output, 0, '?>');

// Logic
addLine($output);
addLine($output, 0, '<!-- Logic -->');
addLine($output);
addLine($output, 0, $struct_key . '.php');
addLine($output, 0, '<?php');
addLine($output);
addLine($output, 0, 'FrameworkManager::loadDAO(\'' .$prefixed_framework_load . $struct_key . '\');');
addLine($output, 0, 'class ' . $logic_name . ' {');
addLine($output, 1, 'public static function get' . $object_name_plural . '() {');
addLine($output, 2, '$dao = new ' . $dao_class_name . '();');
addLine($output, 2, 'return $dao->loadAll();');
addLine($output, 1, '}');

$func_name_elements = array();
$func_params = array();
foreach($primary_keys as $key) {
	$func_name_elements[] = flat_to_camel($key);
	$func_params[] = $key;
}
$primary_function = 'get' . $object_name . 'By' . implode('And', $func_name_elements);

addLine($output, 1, 'public static function ' . $primary_function . '($'.implode(', $', $func_params) .') {');
addLine($output, 2, '$dao = new ' . $dao_class_name . '();');
addLine($output, 2, 'return $dao->load($' . implode(', $', $func_params) . ');');
addLine($output, 1, '}');
addLine($output, 1, 'public static function save(' . $struct_class_name . ' $struct) {');
addLine($output, 2, '$dao = new ' . $dao_class_name . '();');
addLine($output, 2, 'return $dao->save($struct);');
addLine($output, 1, '}');
addLine($output, 0, '}');
addLine($output);
addLine($output, 0, '?>');

addLine($output, 0, '<!-- index.html -->');
addLine($output);
addLine($output, 0, '<@Page templateId="admin-default">');
addLine($output);
addLine($output, 0, '<cms:Content placeHolderId="ph_admin_main">');

addLIne($output, 1, '<!-- Actions -->');
addLIne($output, 1, '<cms:WrapOutput wrapClassId="actionbar">');
addLIne($output, 2, '<cms:WrapOutput wrapClassId="actionbar-action"><a href="edit.html">Add New</a></cms:WrapOutput>');
addLIne($output, 1, '</cms:WrapOutput>');
addLIne($output, 1, '<!-- Browse -->');
addLine($output, 1, '<cms:WrapOutput wrapClassId="panel">');
addLine($output, 2, '<cms:WrapOutput wrapClassId="panel-header">');
addLine($output, 3, '<cms:WrapOutput wrapClassId="panel-title">' . $friendly_name_plural . '</cms:WrapOutput>');
addLine($output, 2, '</cms:WrapOutput>');
addLine($output, 2, '<cms:WrapOutput wrapClassId="panel-body">');
addLine($output, 3, '<cms:DataGrid id="dg_' . $model->getName() . '" class="datagrid" emptyTemplateWrapClassId="panel-body-content">');
addLine($output, 4, '<Columns rowClass=",altrow1">');
foreach($fields as $field) {
	
	$words = explode('_', $field->getName());
	for($i=0; $i < count($words); $i++) {
		$words[$i] = strtoupper(substr($words[$i], 0, 1)) . substr($words[$i], 1);
	}

	if (in_array($field->getName(), Page::get('linked_fields', array()))) {
		addLine($output, 5, '<Column headerText="' . implode(' ', $words) . '"><![CDATA[');
		$keys = array();
		foreach($primary_keys as $primary_key) {
			$keys[] = $primary_key .= '=<Data field="' . $primary_key . '" />';
		}
		addLine($output, 6, '<a href="edit.html?' . implode('&', $keys) . '&p=<?php echo $p; ?>"><Data field="' . $field->getName() . '" /></a>');
		
		addLine($output, 5, ']]></Column>');
	} else {
		addLine($output, 5, '<Column headerText="' . implode(' ', $words) . '" field="' . $field->getName() . '" />');
	}
	
}

addLine($output, 5, '<NoResults><![CDATA[');
addLine($output, 6, 'There are currently not any ' . implode(' ', explode('_', $model->getName())));
addLine($output, 5, ']]></NoResults>');
addLine($output, 4, '</Columns>');
addLine($output, 3, '</cms:DataGrid>');
addLine($output, 2, '</cms:WrapOutput>'); // body
addLine($output, 2, '<cms:WrapOutput wrapClassId="panel-body-content">');
addLine($output, 3, '<cms:Paging id="paging_' . $model->getName() . '" forControlId="dg_' . $model->getName() . '" />');
addLine($output, 2, '</cms:WrapOutput>'); // panel-body-content
addLine($output, 1, '</cms:WrapOutput>'); // panel
addLine($output, 0, '</cms:Content>');
addLine($output);
addLine($output, 0, '<!-- index.html.php -->');
addLine($output);
addLine($output, 0, '<?php');
addLine($output);
addLine($output, 0, 'FrameworkManager::loadLogic(\'' . $prefixed_framework_load . $struct_key . '\');');
addLine($output);
addLine($output, 0, '$p = Page::get(\'p\', 1);');
addLine($output, 0, 'if (!is_numeric($p)) $p = 1;');
addLine($output);
addLine($output, 0, '$rs = ' . $logic_name . '::get' . $object_name_plural . '($p, 10);');
addLine($output);
addLine($output, 0, 'if ($dg_' . $model->getName() . ' = Page::getControlById(\'dg_' . $model->getName() . '\')) {');
addLine($output, 1, '$dg_' . $model->getName() . '->setData($rs);');
addLine($output, 0, '}');
addLine($output, 0, '?>');

/**
 * Generate edit view
 **/
addLine($output);
addLine($output, 0, '<!-- edit.html -->');
addLine($output);
addLine($output, 0, '<@Page templateId="admin-default">');
addLine($output);
addLine($output, 0, '<cms:Content placeHolderId="ph_page_actions" wrapClassId="page-actionbar">');
addLine($output, 1, '<cms:WrapOutput wrapClassId="page-actionbar-action"><a href="<cms:Literal id="back_link" />"><img src="<?php echo ConfigurationManager::get(\'DIR_WS_ADMIN_ASSETS_IMG\') ?>icons/i_back.gif" align="absmiddle" /> Back to <cms:Literal id="back_link_name" /></a></cms:WrapOutput>');
addLine($output, 0, '</cms:Content>');
addLine($output);
addLine($output, 0, '<cms:Content placeHolderId="ph_admin_main">');
addLine($output, 1, '<form method="post">');

foreach($primary_keys as $primary_key) {
	addLine($output, 2, '<cms:Input type="hidden" struct="' . $struct_key . '" structKey="' . $primary_key . '" />');
}
addLine($output, 2, '<cms:WrapOutput wrapClassId="panel">');
addLine($output, 3, '<cms:WrapOutput wrapClassId="panel-header">');
addLine($output, 4, '<cms:WrapOutput wrapClassId="panel-title">Header Name</cms:WrapOutput>');
addLine($output, 3, '</cms:WrapOutput>');
addLine($output, 3, '<cms:WrapOutput wrapClassId="panel-body-content">');
addLine($output, 4, '<table cellspacing="0" cellpadding="0" border="0" class="detaileditview">');

foreach($struct_fields as $struct_field) {
	
	if (!in_array($struct_field, $primary_keys) && !in_array($struct_field, array('created', 'created_by', 'updated', 'updated_by'))) {
		$words = explode('_', $struct_field);
		for($i=0; $i < count($words); $i++) {
			$words[$i] = strtoupper(substr($words[$i], 0, 1)) . substr($words[$i], 1);
		}
		addLine($output, 5, '<tr>');
		addLine($output, 6, '<td class="field"><label>' . implode(' ', $words) . ':</label> </td>');
		addLine($output, 6, '<td class="value"><cms:Input type="text" struct="' . $struct_key . '" structKey="' . $struct_field . '" /></td>');
		addLine($output, 5, '</tr>');
	}
}

addLine($output, 4, '</table>');
addLine($output, 3, '</cms:WrapOutput>');
addLine($output, 3, '<cms:WrapOutput wrapClassId="panel-actionbar-bottom">');
addLine($output, 4, '<cms:Input type="submit" value="Save" class="btn btn-primary" />');
addLine($output, 3, '</cms:WrapOutput>');
addLine($output, 2, '</cms:WrapOutput>');
addLine($output, 1, '</form>');
addLine($output, 0, '</cms:Content>');
addLine($output);
addLine($output, 0, '<!-- edit.html.php -->');
addLine($output);
addLine($output, 0, '<?php');
addLine($output);
addLine($output, 0, 'FrameworkManager::loadLogic(\'' . $prefixed_framework_load . $struct_key . '\');');
if (!empty($plugin_name)) {
	addLine($output, 0, 'FrameworkManager::loadStruct(\'' . $prefixed_framework_load . $struct_key . '\'); // Load plugin struct for Page::getStruct()');
}
addLine($output);
addLine($output, 0, '$' . $var_name . ' = Page::getStruct(\'' . $struct_key . '\');');
addLine($output);
addLine($output, 0, 'if (Page::isPostBack()) {');
addLine($output);

$include_space = false;
foreach($fields as $field) {
	if (in_array($field->getName(), Page::get('required_fields', array()))) {
		$friendly_name = flat_to_words($field->getName());
		$include_space = true;
		addLine($output, 1, 'if (empty($' . $var_name . '->' . $field->getName() . ')) ErrorManager::addError(\'' . $friendly_name . ' is required\');');
	}
}
	
if ($include_space) addLine($output);

addLine($output, 1, 'if (!ErrorManager::anyDisplayErrors()) {');
addLine($output);
addLine($output, 2, '' . $logic_name . '::save($' . $var_name . ');');
addLine($output);
addLine($output, 1, '}');
addLine($output);
addLine($output, 0, '} else {');
addLine($output);

$func_name_elements = array();
$func_params = array();
$check_vars = array();
foreach($primary_keys as $key) {
	$func_name_elements[] = flat_to_camel($key);
	$func_params[] = $key;
	$check_vars[] = '$' . $key . ' = Page::get(\'' . $key . '\')';
}
$primary_function = 'get' . $object_name . 'By' . implode('And', $func_name_elements);

addLine($output, 1, 'if (' . implode(' && ', $check_vars) . ') {');
addLine($output);
addLine($output, 2, '$' . $var_name . ' = ' . $logic_name . '::' . $primary_function . '($' . implode(', $', $func_params) . ');');
addLine($output, 1, '}');
addLine($output);
addLine($output, 0, '}');
addLine($output);
addLine($output, 0, 'Page::setStruct(\'' . $struct_key . '\', $' . $var_name . ');');
addLine($output);
addLine($output, 0, '?>');


######################


echo 'Output:<br /><pre>' . $output;

?>