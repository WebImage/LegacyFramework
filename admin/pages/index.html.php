<?php
ini_set('display_errors', 1);
FrameworkManager::loadLogic('page');
FrameworkManager::loadLogic('adminrecent');

$layout = Page::get('layout');

$total_num_pages = PageLogic::getNumPages();
$rs_recent = AdminRecentLogic::getAdminRecent(array('Admin.Page.Edit'), Membership::getUser()->getId(), 1, 5);

function compare_pages($page_a, $page_b) {
	
	if ($page_a->is_section == 1 && $page_b->is_section == 0) { 
		return -1000;
	} else if ($page_a->is_section == 0 && $page_b->is_section == 1) {
		return 1000;
	} else {
		return strcmp($page_a->title, $page_b->title);
	}

}

// Sort pages by section first, then alphabetical by name
function sort_pages($pages, $parent_id=0) {

	if (isset($pages[$parent_id])) {
		usort($pages[$parent_id], 'compare_pages');
	
		// Sort children
		foreach($pages[$parent_id] as $page) {
			sort_pages($pages[$parent_id], $page->id);
		}
	}
}
function build_page_list($pages, $parent_id=0, $indent_level = 1) {
	
	$output = '';
	
	if (isset($pages[$parent_id]) && count($pages[$parent_id]) > 0) {

		$output .= '<ul class="page-list">';
		
		foreach($pages[$parent_id] as $page) {
		
			$output .= '<li>';
				$output .= '<div class="page-info hover-show-invisible">';
					$output .= '<div class="row">';
						$output .= '<div class="col-md-6">';
							if ($page->is_section == 1) $output .= '<a href="#" class="toggle-children" title="toggle-children"><i class="glyphicon glyphicon-collapse-down"></i></a> ';
							$output .= $page->title_link;
						$output .= '</div>';
						$output .= '<div class="col-md-6">';
							$output .= $page->page_url;
						$output .= '</div>';
					$output .= '</div>';
				$output .= '</div>';
				
				$page->level = $indent_level;
			
				$output .= build_page_list($pages, $page->id, $indent_level+1);
				
			$output .= '</li>';
		}
		
		$output .= '<ul>';					
	}
	
	return $output;

}

$rs_pages = PageLogic::getPages();

/**
 * Create a home page if one does not already exist
 */
if ($rs_pages->getCount() == 0) {
	// Makes sure that a new home page creation does not happen - which could potentially happen if the pages table gets modified resulting in a SQL error that causes the page retrieval process to fail
	if (!$count = Page::get('count')) $count = 0;
	$count ++;
	
	$new_page = PageLogic::createHomePage();
	if ($count > 1) {
		echo 'For some reason this page is not working.  Please contact support and provide them (copy-and-paste) any information that appears below:<hr />';
		echo '<pre>';
		print_r($new_page);
		echo '</pre>';
		$error = mysqli_error(ConnectionManager::getConnection());
		if (strlen($error) > 0) echo 'MySQL Error: ' . $error . '<br />';
		exit;
	}
	Page::redirect('index.html?message=Home+page+created&count=' . $count);
}

$max_title_length = 30;
/**
 * Add additional details to each page
 */
while ($page = $rs_pages->getNext()) {
	
	$page->template_link = ConfigurationManager::get('DIR_WS_ADMIN') . 'pages/edit.html?pageid=' . $page->id;
	$page->edit_page_link = substr(ConfigurationManager::get('DIR_WS_ADMIN_CONTENT'), 0, -1) . $page->page_url;
	
	$title_link = '';
	
	$page_title = $page->title;
	if ($page->page_url == '/index.html') $page_title = $page->is_section == 1 ? 'Root':'Home Page';
	
	if (strlen($page_title) > $max_title_length) $page_title = substr($page_title, 0, $max_title_length-3) . '...';
	
	// Icon
	if ($page->is_section == 1 && $page->page_url == '/index.html') $icon = 'glyphicon glyphicon-home';
	else if ($page->is_section == 1) $icon = 'glyphicon glyphicon-folder-close';
	else $icon = 'glyphicon glyphicon-file';
	
	$title_link .= '<a href="' . $page->edit_page_link . '"';
	
	$title_link .= ' xstyle="color:#777;font-size:14px;font-weight:bold;"';
	$title_link .= '>';
	$title_link .= '<i class="'.  $icon . '"></i> ';
	if ($page->is_section == 1) $title_link .= '<strong>';
	$title_link .= $page_title;
	if ($page->is_section == 1) $title_link .= '</strong>';
	$title_link .= '</a>';
	
	$title_link .= '<div class="right" style="float:right;">';
		if ($page->is_section == 1 && $layout != 'grid') {
			$title_link .= ' <a href="' . ConfigurationManager::get('DIR_WS_ADMIN') . 'pages/edit.html?parentid=' . $page->id . '" class="invisible" title="Add a sub page to &quot;' . htmlentities($page->title) . '&quot;"><i class="glyphicon glyphicon-plus-sign"></i></a>';
		}
		$title_link .= ' <a href="' . $page->template_link . '" class="invisible" title="Change page title, meta tags, or template"><i class="glyphicon glyphicon-pencil"></i></a>';
	$title_link .= '</div>';
	
	$page->title_link = $title_link;
	
}

/**
 * Separate pages into a hierachy by parent_id
 */
$pages = array();
while ($page_struct = $rs_pages->getNext()) {

	if (!isset($pages[$page_struct->parent_id])) $pages[$page_struct->parent_id] = array();
	$pages[$page_struct->parent_id][] = $page_struct;

}

// Sort the pages
sort_pages($pages);

if ($layout == 'grid') {
	$page_control = 'dl_page_grid';
} else {
	$page_control = 'dl_page_list';
}

if ($dl_pages = Page::getControlById($page_control)) $dl_pages->setData($rs_pages);
if ($dl_recent = Page::getControlById('dl_recent')) $dl_recent->setData($rs_recent);

?>