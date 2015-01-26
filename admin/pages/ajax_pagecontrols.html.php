<?php

FrameworkManager::loadLogic('page');
FrameworkManager::loadLogic('pagecontrol');

if ($page_id = Page::get('pageid')) {
	
	$page = PageLogic::getPageById($page_id);
	
	$page_controls = PageControlLogic::getControlsByPageId($page_id);
	$rs_page_controls = new ResultSet();
	while ($page_control = $page_controls->getNext()) {
		$page_control->title = $page_control->placeholder . ' - ' . $page_control->class_name . ' (' . $page_control->sortorder . ')';
		$rs_page_controls->add($page_control);
	}
	
	$cbo_page_controls = Page::getControlById('cbo_page_controls');
	$cbo_page_controls->setData($rs_page_controls);
}

?>