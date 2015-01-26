<?php
ini_set('display_errors', 1);error_reporting(E_ALL);
FrameworkManager::loadLogic('control');
$control = Page::getStruct('control');

if (Page::isPostBack()) {
	
	if (empty($control->label)) ErrorManager::addError('Your control must have a label');
	if (empty($control->class_name)) ErrorManager::addError('Please enter a class name for your control');
	
	if (!ErrorManager::anyDisplayErrors()) {
		
		$control = ControlLogic::save($control);
	
		include_once( PathManager::translate($control->file_src) );
		
		if (!class_exists($control->class_name)) NotificationManager::addMessage('The class ' . $control->class_name . ' could not be found');
		else {		
			Page::redirect('index.html');
		}
	}
	
} else {
	if ($control_id = Page::get('id')) {
		$control = ControlLogic::getControlById($control_id);
	} else {
		$control->enable = 1;
	}
}

$yes_no = array(
	1 => 'Yes',
	0 => 'No'
);
$rs_rows = ResultSetHelper::buildResultSetFromArray($yes_no);

if ($enable = Page::getControlById('enable')) $enable->setData($rs_rows);

Page::setStruct('control', $control);

?>