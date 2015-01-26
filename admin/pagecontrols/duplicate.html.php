<?php

FrameworkManager::loadLogic('page');

$page_hierarchy= PageLogic::getFlatPageHierarchy();

$pages = new ResultSet();

while ($page = $page_hierarchy->getNext()) {
	$title = '';
	for ($i=1; $i < $page->level; $i++) {
		$title .= '&nbsp;&nbsp;&nbsp;&nbsp;';
	}
	
	$title .= $page->title;
	
	$page->title = $title;
	$pages->add($page);
}

$lst_pages = Page::getControlById('lst_pages');
$lst_pages->setData($pages);

?>