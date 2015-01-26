<?php

FrameworkManager::loadLogic('page');

$page_hierarchy= PageLogic::getFlatPageHierarchy();

#$pages = new ResultSet();
#$max_title_length = 30;

while ($page = $page_hierarchy->getNext()) {
	
	unset($page->created);
	unset($page->created_by);
	unset($page->updated);
	unset($page->updated_by);
	
	if ($page->page_url == '/index.html') $page->title = 'Home Page';
	#if (strlen($page_title) > $max_title_length) $page_title = substr($page_title, 0, $max_title_length-3) . '...';
	
	$space = '';
	for ($i=1; $i < $page->level; $i++) {
		$space .= '&nbsp;&nbsp;&nbsp;&nbsp;';
	}
	$page->space = $space;
	
	
	if ($page->page_url == '/index.html') {
		$page->icon .= '<img src="' . ConfigurationManager::get('DIR_WS_ADMIN_ASSETS_IMG') . 'icons/i_house.gif" style="margin-right:5px;" width="16" height="16" align="absmiddle" />';
	} else if ($page->is_section == 1) {
		$page->icon .= '<img src="' . ConfigurationManager::get('DIR_WS_ADMIN_ASSETS_IMG') . 'icons/i_folder.gif" style="margin-right:5px;" width="16" height="16" align="absmiddle" />';
	} else {
		$page->icon .= '<img src="' . ConfigurationManager::get('DIR_WS_ADMIN_ASSETS_IMG') . 'icons/i_page.gif" style="margin-right:5px;" width="16" height="16" align="absmiddle" />';
	}

	#$title_link .= '<a href="edit.html?pageid=' . $page->id . '">' . $page_title . '</a>';
	$page->full_link = 'http://' . ConfigurationManager::get('DOMAIN') . $page->page_url;
	
	#$page->link .= '<a href="' . substr(ConfigurationManager::get('DIR_WS_ADMIN_CONTENT'), 0, -1) . $page->page_url . '" title="Edit: ' . htmlentities($page->title) . '">' . $page_title . '</a>';
	
	#if ($page->is_section == 1) {
	#	$page->link.= ' <sub>[<a href="edit.html?parentid=' . $page->id . '" style="color:#999;" title="Add a sub page to &quot;' . htmlentities($page->title) . '&quot;">+</a>]</sub>';
	#}
	
	#$title_link .= ' [<a href="edit.html?pageid=' . $page->id . '" style="color:#999;" title="Edit page title, meta keywords/description, page template for &quot;' . htmlentities($page->title) . '&quot;">Meta</a>]';
	
	#$page->title_link = $title_link;
	#$pages->add($page);
	$page_hierarchy->setAt($page_hierarchy->getCurrentIndex(), $page);
}


$dl_pages = Page::getControlById('dl_pages');
$dl_pages->setData($page_hierarchy);

?>