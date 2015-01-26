<?php

FrameworkManager::loadLibrary('formbuilder');

FrameworkManager::loadLogic('role');
FrameworkManager::loadLogic('parameter');

$rs_roles = RoleLogic::getVisibleRoles();

if ($dl_roles = Page::getControlById('dl_roles')) {
	$dl_roles->setData($rs_roles);
}

$generated_internal_name = 'enter name';
$key_assigned = Page::get('key_assigned', 0);

$param_role = Page::get('paramrole', array());

$parameter_form_action_label = Page::get('key') ? 'Edit':'New';

if (Page::isPostBack()) {	
	
	$name		= Page::get('name');
	$description	= Page::get('description');
	$key		= Page::get('key');
	$sortorder	= Page::get('sortorder');
	$group		= Page::get('group');
	$group_other	= Page::get('groupother');
	$input_element	= Page::get('inputelement');
	
	$required	= Page::get('required');
	
	if (!is_numeric($sortorder)) $sortorder = 1;
	
	if (empty($name) || empty($key)) ErrorManager::addError('Missing name or key values.');
	if (empty($input_element)) ErrorManager::addError('Select an input element');
	
	if (!ErrorManager::anyDisplayErrors()) {
		
		if ($group == '--other--') $group = $group_other;
		
		if (!is_array($param_role)) $param_role = array();
		
		if ($rs_roles->getCount() == count($param_role)) $param_role = array(); // If the selected number of roles equals the total number of possible roles, then assume that this applies to all roles.  We therefore deselect all.
		
		$config = new ConfigDictionary();
		$config->set('limitRoles', $param_role);
		$config->set('required', ($required == 1));
		
		FrameworkManager::loadLogic('parameter');
		FrameworkManager::loadStruct('parameter');
		
		$parameter_struct = new ParameterStruct();
		$parameter_struct->config = $config->toString();
		$parameter_struct->description = $description;
		$parameter_struct->group = $group;
		$parameter_struct->input_element = $input_element;
		$parameter_struct->key = $key;
		$parameter_struct->name = $name;
		$parameter_struct->sortorder = $sortorder;
		$parameter_struct->type = 'Membership';
		
		ParameterLogic::save($parameter_struct);
		
		Page::redirect('parameters.html'); // Refresh parameter list
		
	}
	
} else {
	
	if ($key = Page::get('key')) {
		
		if ($parameter_struct = ParameterLogic::getParameterByTypeAndKey('Membership', $key)) {
			
			$config = ConfigDictionary::createFromString($parameter_struct->config);
		
			if ($limit_roles = $config->get('limitRoles')) {
				
				foreach($limit_roles as $limit_role) {
					array_push($param_role, $limit_role);
				}
			}
			
			$required = ($config->get('required') === true);
			
			Page::set('name', $parameter_struct->name);
			Page::set('description', $parameter_struct->description);
			Page::set('inputelement', $parameter_struct->input_element);
			Page::set('key', $parameter_struct->key);
			Page::set('sortorder', $parameter_struct->sortorder);
			Page::set('group', $parameter_struct->group);
			Page::set('required', ($required ? 1 : 0));
			
			$key_assigned = 1;
			
		}
		
	} else if ($delete = Page::get('delete')) {
		
		ParameterLogic::deleteParameter('Membership', $delete);
		
	}
	
}

if ($ctl_parameter_form_action = Page::getControlById('parameter_form_action')) {
	$ctl_parameter_form_action->setText($parameter_form_action_label);
}

// Create an easy to use role lookup by ID
$roles_lookup = new Dictionary();
while ($role_struct = $rs_roles->getNext()) {
	$roles_lookup->set($role_struct->id, $role_struct->name);
}

$groups = array();

if ($dg_parameters = Page::getControlById('dg_parameters')) {
	
	$rs_parameters = ParameterLogic::getParametersByType('Membership');

	// Retrieve roles for each parameter
	while ($parameter_struct = $rs_parameters->getNext()) {
		
		if (!in_array($parameter_struct->group, $groups)) array_push($groups, $parameter_struct->group);
		
		$config = ConfigDictionary::createFromString($parameter_struct->config);
		$limit_roles = $config->get('limitRoles');
		if (!is_array($limit_roles)) $limit_roles = array();
		
		$edit_roles = '';
		
		#$txt_hidden = new HiddenElement('curparam_' . $parameter_struct->key, implode($limit_roles));
		#$check = new CheckboxInputElement('', 'param_' . $parameter_struct->key);
		
		$selected_roles = array();
		
		while ($role_struct = $rs_roles->getNext()) {
			
			#$check->addChoice($role_struct->id, $role_struct->name);
			#if (in_array($role_struct->id, $limit_roles) || count($limit_roles) == 0) array_push($selected_roles, $role_struct->id);
			if (in_array($role_struct->name, $limit_roles) || count($limit_roles) == 0) array_push($selected_roles, $role_struct->name);
			
		}
		#$check->setValue($selected_roles);
		
		#$edit_roles = $txt_hidden->render() . $check->render();
		$edit_roles = implode(', ', $selected_roles);
		
		$parameter_struct->required = ($config->get('required') === true) ? 1 : 0;
		$parameter_struct->edit_roles = $edit_roles;
	}
	
	$dg_parameters->setData($rs_parameters);
	
}

// Setup groups dropdown
$rs_groups = new ResultSet();
$obj = new stdClass();

$obj->id = '--other--';
$obj->name = '-- Other --';
$rs_groups->add($obj);
sort($groups);

foreach($groups as $group) {
	
	$obj = new stdClass();
	$obj->id = $group;
	$obj->name = $group;
	$rs_groups->add($obj);
	
}

if ($cbo_group = Page::getControlById('group')) {
	
	$cbo_group->setData($rs_groups);
	
}


if ($key_assigned == 1) {
	$generated_internal_name = Page::get('key');
}

if ($ctl_generated_internal_name = Page::getControlById('generated_internal_name')) {
	$ctl_generated_internal_name->setText($generated_internal_name);
}

if ($ctl_key_assigned = Page::getControlById('key_assigned')) {
	$ctl_key_assigned->setValue($key_assigned);
}

while ($role = $rs_roles->getNext()) {
	if (in_array($role->name, $param_role) || count($param_role) == 0) $role->selected = ' checked="true"';
	else $role->selected = '';
}

$input_elements = array(
	'TextInputElement'	=> 'Text',
	'WebsiteInputElement'	=> 'Website',
	'FileUploadElement'	=> 'File/Image'
	);
$rs_input_elements = ResultSetHelper::buildResultSetFromArray($input_elements);

if ($input_element = Page::getControlById('inputelement')) {
	
	$input_element->setData($rs_input_elements);
	
}

while ($parameter = $rs_parameters->getNext()) {
	
	$input_element_description = 'Unknown';
	
	if (isset($input_elements[$parameter->input_element])) $input_element_description = $input_elements[$parameter->input_element];
	
	$parameter->input_element_description = $input_element_description;
	
}

?>