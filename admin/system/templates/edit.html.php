<?php

FrameworkManager::loadLogic('template');

$template = Page::getStruct('template');

if (Page::isPostBack()) {
	
	if (empty($template->name)) ErrorManager::addError('Name is required.');
	else if (strlen($template->name) > 20) ErrorManager::addError('Name cannot be more than 20 characters.');
	
	if (empty($template->file_src) && empty($template->file_contents)) ErrorManager::addError('You must specify a value for either file path or contents.');
	
	if (!ErrorManager::anyDisplayErrors() && $template = TemplateLogic::save($template)) {
		Page::redirect('index.html');
	}
		
} else {
	if ($template_id = Page::get('tplid')) {
		$template = TemplateLogic::getTemplateById($template_id);
	}
}

if (empty($template->type)) $template->type = 'Page';
Page::setStruct('template', $template);

?>