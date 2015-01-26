<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);
Page::addScript(ConfigurationManager::get('DIR_WS_ADMIN_ASSETS_JS'). 'global.js');

$is_new_page = true;

FrameworkManager::loadLibrary('string.url');
FrameworkManager::loadLogic('page');
FrameworkManager::loadLogic('pageparameter');
FrameworkManager::loadLogic('adminrecent');
FrameworkManager::loadDAO('page');

$page = Page::getStruct('page');

$file_already_set = false;

if (Page::isPostBack()) {
	
	if (empty($page->title)) ErrorManager::addError('Title is required');
	
	$check_parent = true;
	
	if (!empty($page->id)) {
		
		$original_page = PageLogic::getPageById($page->id);
		
		if ($original_page->parent_id == 0) {
			$check_parent = false;
			$page->is_section = 1;
		}
	}
	
	if (empty($page->parent_id) && $check_parent) {
		ErrorManager::addError('Please specify a section that this page belongs to.');
	} else if ($page->parent_id == 0) {
		$page->page_url = '/' . Page::get('page_key');
	} else {
		$parent_page = PageLogic::getPageById($page->parent_id);
		$path_parts = explode('/', $parent_page->page_url);
		array_pop($path_parts);
		$base = implode('/', $path_parts) . '/';
		$page->page_url = $base . Page::get('page_key');
	}
	
	if (empty($page->template_id)) ErrorManager::addError('Template is required for the page to display correctly.');
	
	if (empty($page->is_section)) $page->is_section = 0;
	
	$redirect_path = 'index.html';
	$is_new_page = (empty($page->id)); // Whether the page is a brand new page (id not set)
	
	// If this is a brand new page, then forward to the page to be created
	if ($is_new_page && !empty($page->page_url)) $redirect_path = PathManager::getAdminContentPath($page->page_url);	
		
	if (!ErrorManager::anyDisplayErrors() && $page = PageLogic::save($page)) {
		
		$url = new CWI_STRING_Url( Page::getRequestedPath() );
		$url->setQueryValue('pageid', $page->id);
		
		AdminRecentLogic::create('Admin.Page.Edit', $page->title . ' (details)', $url, Membership::getUser()->getId());
		
		Page::setStruct('page', $page);
		$forward_pages = true;
		
		$is_new_page = false;
		
		if (!ErrorManager::anyDisplayErrors() && !$is_new_page) {
			// Save Existing Params
			$parameters = Page::get('parameters', array());
			foreach($parameters as $parameter_id) {
				$save_param			= new PageParameterStruct();
				$save_param->id			= $parameter_id;
				$save_param->parameter		= Page::get('parameter_name_' . $parameter_id);
				$save_param->value		= Page::get('parameter_value_' . $parameter_id);
				$save_param->page_id		= $page->id;
				PageParameterLogic::save($save_param);
			}
			
			// Save New Param
			$new_param = Page::get('newparam');
			$new_param_value = Page::get('newparamvalue');
			
			if (!empty($new_param)) {
				$parameter_struct			= new PageParameterStruct();
				$parameter_struct->parameter		= $new_param;
				$parameter_struct->page_id		= $page->id;
				$parameter_struct->value		= $new_param_value;
				PageParameterLogic::save($parameter_struct);
				
				NotificationManager::addMessage('New page parameter successfully saved.');
				$forward_pages = false;
			}
		}
		if ($forward_pages) Page::redirect($redirect_path);
	}
	
	if (!empty($page->id)) $file_already_set = true;
	
/*	$debug_messages = DebugManager::getMessages();
echo '<pre>DEBUG';
while ($message = $debug_messages->getNext()) {
	echo $message . '<hr />';
}
echo '<hr>';
echo mysql_error();
exit;*/
} else {
	
	$action = Page::get('action');
	switch ($action) {
		case 'deleteparam':
			$param_id = Page::get('paramid');
			PageParameterLogic::delete($param_id);
			break;
	}
	
	if ($page_id = Page::get('pageid')) {
		
		$page = PageLogic::getPageById($page_id);
		$is_new_page = false;
		$file_already_set = true;
		
	} else if ($parent_id = Page::get('parentid')) {
		
		$page->parent_id = $parent_id;
		
	} else if ($missing_url = Page::get('missingurl')) {
		
		$file_already_set = true;
		
		NotificationManager::addMessage('You were forwarded to this page because the page you requested could not be found.  Create a page for this location below.');
		
		$parts = explode('/', $missing_url);
		$parts_count = count($parts);
		$parent_url = '';
		
		if (count($parts) >= 2) {
			#$page->page_key = $parts[count($parts)-2] . '/' . $parts[count($parts)-1];
			Page::set('page_key', $parts[count($parts)-2] . '/' . $parts[count($parts)-1]);
		}


#http://dev.athenacms.com/admin/pages/edit.html?missingurl=%2Frootlevelfile.html
#http://dev.athenacms.com/admin/pages/edit.html?missingurl=%2Fabout%2Ffakecategory%2Ffakefile.html
#http://dev.athenacms.com/admin/pages/edit.html?missingurl=%2Fabout%2Fproducts%2Frealdirectory.html
#http://dev.athenacms.com/admin/pages/edit.html?missingurl=%2Fnewdir%2Frealfile.html

		if ($parts[$parts_count-1] == 'index.html') {
			
			$page->is_section = 1;
			
		} else {
			
			array_pop($parts);
			array_push($parts, 'index.html');
			
			$parent_url = implode('/', $parts);
			
			
		}
		
		if ($parent_page = PageLogic::getPageByUrl($parent_url)) {
			
			$page->parent_id = $parent_page->id;
			
		} else if ($home = PageLogic::getPageByUrl('/index,.html')) { // Fall back to home page if nothing else
			
			$page->parent_id = $home->id;
			
			
		}
		
		$page->page_url = $missing_url;
		
	}
	
	if ($page->page_url == '/index.html') {
		if ($show_parent_info = Page::getControlById('show_parent_info')) $show_parent_info->visible(false);
	}
	
	if (!empty($page->page_url)) {
		$url_parts = explode('/', $page->page_url); 
		$page_key = array_pop($url_parts);
		Page::set('page_key', $page_key);
	}

}

if (!$is_new_page) {

	// Parameters
	$option_parameters = PageParameterLogic::getPageParametersByPageId($page->id);
	
	if ($dl_page_params = Page::getControlById('dl_page_params')) $dl_page_params->setData($option_parameters);
	
}

$parent_page_hierarchy= PageLogic::getFlatSectionHierarchy();
$parent_pages = new ResultSet();

while ($parent_page = $parent_page_hierarchy->getNext()) {
	
	if ($parent_page->id != $page->id) {
		
		$max_title_length = 30;
		
		if (strlen($parent_page->title) > $max_title_length) $parent_page->title = substr($parent_page->title, 0, $max_title_length-3) . '...';
		
		if ($parent_page->page_url == '/index.html') {
			$parent_page->title = 'Home Page / Root';
		}
		
		$title = '';
		for ($i=1; $i < $parent_page->level; $i++) {
			$title .= '&nbsp;&nbsp;&nbsp;&nbsp;';
		}
		
		#$title_link .= '<a href="edit.html?pageid=' . $parent_page->id . '">' . $parent_page->title . '</a>';
		$title .= $parent_page->title;
		
		$parent_page->title = $title;
		$parent_pages->add($parent_page);
	}
}

if ($cbo_parent_id = Page::getControlById('parent_id')) $cbo_parent_id->setData($parent_pages);

// Templates
FrameworkManager::loadLogic('template');
$available_templates = TemplateLogic::getTemplates('Page');
if ($cbo_template = Page::getControlById('template_id')) $cbo_template->setData($available_templates);

// If only one template available, select it by default
if ($available_templates->getCount() == 1 && empty($page->template_id)) {
	$default_template = $available_templates->getAt(0);
	$page->template_id = $default_template->id;
}

$page->_file_already_set = $file_already_set;

$page_title = empty($page->title) ? 'New Page' : $page->title;
if ($ctl_page_title = Page::getControlById('page_title')) $ctl_page_title->setText($page_title);


Page::setStruct('page', $page);

?>