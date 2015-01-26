<?php

FrameworkManager::loadManager('theme');
FrameworkManager::loadLibrary('controls.editable.general');

if ($content_stylesheets = CWI_MANAGER_ThemeManager::getAdminContentStylesheets( ConfigurationManager::get('THEME_ADMIN_CONTENT') )) {
	while ($content_stylesheet = $content_stylesheets->getNext()) {
		Page::getCurrentPageRequest()->getPageResponse()->addStylesheet( $content_stylesheet );
	}
}

$template_id = Page::get('tplid', 1);

$context = Page::getCurrentPageRequest()->getPageResponse()->getContext();
$context->set('control_edit_context', EDITABLE_EDITCONTEXT_TEMPLATE);
$context->set('control_edit_context_template_id', $template_id);
#$context->set('control_edit_context_template_select_region', true);

FrameworkManager::loadLogic('template');

$template = TemplateLogic::getTemplateById($template_id);

if (empty($template->file_contents)) {
	Page::loadControl($template->file_src);
} else {
	Page::loadControlByText($template->file_contents);
}
#echo '<pre>';
#print_r($template);
#echo '</pre>';
FrameworkManager::loadLogic('pagecontrol');
$db_controls = PageControlLogic::getControlsByTemplateId($template_id);
			
while ($get_control = $db_controls->getNext()) {
	
	$control = PageControlLogic::buildControlByPageControlId($get_control);
	if ($parent_control = Page::getControlById($get_control->placeholder)) {
		// Create hierarchal controls
		$parent_control->addControl($control);
		
	}
}
?>