<?php

if (Page::getCurrentPageRequest()->getPageResponse()->getOutputType() != PageResponse::OUTPUT_TYPE_JSON) die('Invalid request type');

FrameworkManager::loadLogic('page');

$ajax_response = new stdClass();

$title		= Page::get('title');
$key		= null;
$status		= PageLogic::STATUS_DRAFT;
$parent_id	= Page::get('parentid');
$template_id	= Page::get('templateid');
$page_type	= 'S';

if (empty($key)) {
	FrameworkManager::loadLibrary('string.helper');
	$key = CWI_STRING_Helper::strToSefKey($title);
}
	
$page_url = '';	
if ($parent_page = PageLogic::getPageById($parent_id)) {
	$path_parts = explode('/', $parent_page->page_url);
	array_pop($path_parts);
	$base = implode('/', $path_parts) . '/';
	$page_url = $base . $key . '.html';
} else {
	ErrorManager::addError('The selected section is invalid');
}

if (!ErrorManager::anyDisplayErrors()) {
	if (empty($title)) ErrorManager::addError('Title required');
	else if (empty($parent_id)) ErrorManager::addError('Section required');
	else if (empty($template_id)) ErrorManager::addError('Template required');
}

if (ErrorManager::anyDisplayErrors()) {
	$error_messages = ErrorManager::getDisplayErrors();
	
	$ajax_response->success		= false;
	$ajax_response->error		= $error_messages->getAt(0);
} else {
	$page_struct = PageLogic::createQuickPage($title, $status, $parent_id, $template_id, $page_url, $page_type);
	
	$ajax_response->success		= true;
	$ajax_response->id		= $page_struct->id;
	$ajax_response->title		= $title;
	$ajax_response->status		= $status;
	$ajax_response->parent_id	= $parent_id;
	$ajax_response->template_id	= $template_id;
	$ajax_response->page_url	= $page_url;
	$ajax_response->admin_page_url	= ConfigurationManager::get('DIR_WS_ADMIN_CONTENT') . substr($page_url, 1);	
}
echo json_encode($ajax_response);

exit;


?>