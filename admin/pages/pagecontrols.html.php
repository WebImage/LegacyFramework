<?php

if (!$page_id = Page::get('pageid')) Page::redirect(ConfigurationManager::get('DIR_WS_ADMIN') . 'pages');

FrameworkManager::loadLogic('page');
FrameworkManager::loadLogic('pagecontrol');

$this_page = PageLogic::getPageById($page_id);
$lbl_page_title = Page::getControlById('lbl_page_title');
$lbl_page_title->setText($this_page->title);
$lbl_page_url = Page::getControlById('lbl_page_url');
$lbl_page_url->setText($this_page->page_url);

if (Page::isPostBack()) {
	if ($clone_control = Page::getStruct('pagecontrol')) {

		$clone_control_struct = PageControlLogic::getPageControlById($clone_control->mirror_id);

		if (!empty($clone_control_struct->mirror_id)) $clone_control->mirror_id = $clone_control_struct->mirror_id;
		
		$sortorder = PageControlLogic::getNextSortOrder($page_id, $clone_control->placeholder);
		
		$clone_control->page_id = $page_id;
		$clone_control->sortorder = $sortorder;
		$clone_control->control_id = $clone_control_struct->control_id;

		PageControlLogic::save($clone_control);
	}
}

$page_hierarchy= PageLogic::getFlatPageHierarchy();

$pages = new ResultSet();

while ($page = $page_hierarchy->getNext()) {
	$title = '';
	for ($i=1; $i < $page->level; $i++) {
		$title .= '&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;';
	}
	
	$title .= $page->title;
	$page->title = $title;
	$pages->add($page);
}
$cbo_pages = Page::getControlById('cbo_pages');
$cbo_pages->setData($pages);

// Get Template EditableRegions

if ($template_file = PathManager::translate($this_page->template_src)) {

	$template_source = file_get_contents($template_file);

	$rs_template = new ResultSet();
	
	preg_match_all('/<cms:EditableRegion.*?id="(.+?)".*?>/', $template_source, $placeholders);
	
	if (isset($placeholders[1])) {
		foreach($placeholders[1] as $placeholder) {
			$tpl = new stdClass();
			$tpl->id = $placeholder;
			$rs_template->add($tpl);
		}
		
		$cbo_placeholder = Page::getControlById('cbo_placeholder');
		$cbo_placeholder->setData($rs_template);
	}
	
	
}

$page_controls = PageControlLogic::getControlsByPageId($page_id);

$dl_page_controls = Page::getControlById('dl_page_controls');
$dl_page_controls->setData($page_controls);

?>