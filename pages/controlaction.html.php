<?php
ini_set('display_errors', 1);
FrameworkManager::loadLogic('pagecontrol');
FrameworkManager::loadStruct('pagecontrol');

$edit_mode = Page::get('editmode');
$edit_context = Page::get('editcontext');
$window_mode = Page::get('windowmode');

$page_control_id = Page::get('pagecontrolid');

if (!empty($page_control_id)) { // Existing control

	$control = PageControlLogic::buildControlByPageControlId($page_control_id, $edit_mode, $edit_context, $window_mode);

} else if ($duplicate_page_control_id = Page::get('duplicatepagecontrolid')) { // Duplicate an existing page control

	$duplicate_page_control =  PageControlLogic::getPageControlById($duplicate_page_control_id);
	$duplicate_page_control->id = null; // Make sure this gets saved as a new contol
	$duplicate_page_control->mirror_id = $duplicate_page_control_id;
	$duplicate_page_control->is_favorite = 0; // Make sure this is not re-added as a favorite
	$duplicate_page_control->favorite_title = '';
	$duplicate_page_control->page_id = Page::get('pageid');
	$duplicate_page_control->placeholder = Page::get('placeholder');
	$duplicate_page_control->sortorder = Page::get('sortorder');
	$duplicate_page_control->template_id = Page::get('templateid');
	
	PageControlLogic::save($duplicate_page_control);
	
	$control = PageControlLogic::buildControlByPageControlId($duplicate_page_control->id, $edit_mode, $edit_context, $window_mode);
		
} else if ($control_id = Page::get('controltype')) { // Brand new Control

	$page_control_struct = new PageControlStruct();
	$page_control_struct->control_id = $control_id;
	$page_control_struct->page_id = Page::get('pageid');
	$page_control_struct->placeholder = Page::get('placeholder');
	$page_control_struct->sortorder = Page::get('sortorder');
	$page_control_struct->template_id = Page::get('templateid');
	
	/**
	 * newcontrolid = when a new control is first requested for creation the newsequenceid will be set
	 * - otherwise -
	 * controlid = will be set when a new control is in the process of being created and we need to us "callaction" to manipulated the new object
	 **/
	$control_id = null;
	if (Page::get('newcontrolid')) {
		$control_id = Page::get('newcontrolid');
	} else if (Page::get('controlid')) {
		$control_id = Page::get('controlid');
	}
	
	$control = PageControlLogic::buildNewControl($page_control_struct, $edit_mode, $edit_context, $window_mode, $control_id);
	
} else {
	die('INVALID');
}

if (Page::isPostBack()) {
	
	if ($call_action = Page::get('callaction')) {
		
		if ($call_action == 'postback') {
			$json_response = $control->handlePostBack(Page::getAll());
			echo $json_response->getJson();
		} else {
			$response_type = Page::get('responsetype', 'json'); // Right now this is not being passed
			$response = $control->handleActionRequest($call_action, Page::getAll(), $response_type);
			/**
			 * should contain callback
			 * control_123.handleActionCallback($call_action, values, response_type)
			 **/
			echo $response;
		}
	}
	
} else {
	
	$rendered_control = $control->render();
	
	$header = '';
	$javascript_files = array();
	
	// Stylesheet
	while ($style= Page::getStyleSheets()->getNext()) {
		$header .= $style->renderHtml();
	}
	
	// Scripts
	while ($script = Page::getScripts()->getNext()) {
		#$header .= $script->renderHtml();
		$javascript_files[] = $script->getSource();//$script->renderHtml();
	}
	
	// Script text
	$script_text = Page::getScriptText();
	if (!empty($script_text)) $header .= '<script type="text/javascript" language="javascript">' . $script_text . '</script>';
	
	$output = $header . $rendered_control;
	
	#echo substr($rendered_control, 0, 48) . '|' . substr($rendered_control, -6);
	#echo nl2br(htmlentities($output));exit;
	
	$json_response = new stdClass();
	$json_response->html = $output;
	if (count($javascript_files) > 0) {
		$json_response->javascriptFiles = $javascript_files;
	}
	echo json_encode($json_response);
	// Exit script, otherwise Page::render() will finishing running and output extra text (bad)
	exit;
	#mail('rjones@corporatewebimage.com', 'JSON', json_encode($json_response));
	//echo $output;
}

?>