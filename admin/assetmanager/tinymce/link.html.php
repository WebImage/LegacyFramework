<?php

$max_title_length = 30;

FrameworkManager::loadLogic('page');

$page_hierarchy= PageLogic::getFlatPageHierarchy();

while ($page = $page_hierarchy->getNext()) {
	
	if ($page->page_url == '/index.html') $page->title = 'Home Page';
	
	$space = '';
	for ($i=1; $i < $page->level; $i++) {
		$space .= '&nbsp;&nbsp;&nbsp;';
	}
	$this_max_title_length = $max_title_length - (2 * $page->level);
	if (strlen($page->title) > $this_max_title_length) $page->title = substr($page->title, 0, $this_max_title_length-3) . '...';
	
	$page->title = $space . $page->title;
	
	#$page->full_link = 'http://' . ConfigurationManager::get('DOMAIN') . $page->page_url;
	$page->full_link = substr( ConfigurationManager::get('DIR_WS_HOME'), 0, -1) . $page->page_url;
	
	$page_hierarchy->setAt($page_hierarchy->getCurrentIndex(), $page);
}


$cbo_pages = Page::getControlById('cbo_pages');
$cbo_pages->setData($page_hierarchy);

?>