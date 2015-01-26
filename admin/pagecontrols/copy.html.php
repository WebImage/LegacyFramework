<?php

FrameworkManager::loadLogic('page');
FrameworkManager::loadLogic('pagecontrol');
FrameworkManager::loadStruct('pagecontrol');

$reject_path = ConfigurationManager::get('DIR_WS_ADMIN') . 'pages/';

if (!$page_id		= Page::get('pageid')) 		Page::redirect($reject_path);
if (!$placeholder	= Page::get('placeholder')) 	Page::redirect($reject_path);
if (!$control_id	= Page::get('controlid')) 	Page::redirect($reject_path);

$page_struct		= PageLogic::getPageById($page_id);
$forward_path		= PathManager::getAdminContentPath($page_struct->page_url);

if (!$clone_control_struct = PageControlLogic::getPageControlById($control_id)) Page::redirect($reject_path);

$sortorder = PageControlLogic::getNextSortOrder($page_id, $clone_control_struct->placeholder);

$page_control_struct = new PageControlStruct();
if (!empty($clone_control_struct->mirror_id)) $page_control_struct->mirror_id = $clone_control_struct->mirror_id;
else $page_control_struct->mirror_id = $control_id;
$page_control_struct->page_id		= $page_id;
$page_control_struct->sortorder		= $sortorder;
$page_control_struct->control_id	= $clone_control_struct->control_id;
$page_control_struct->placeholder	= $placeholder;

PageControlLogic::save($page_control_struct);

Page::redirect($forward_path);

?>