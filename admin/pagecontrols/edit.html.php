<?php

FrameworkManager::loadLogic('page');
FrameworkManager::loadLogic('pagecontrol');
$page_control = Page::getStruct('pagecontrol');

/*
 *
 *	Get the type of page control to display
 *
 */
if ($page_control_id = Page::get('pagecontrolid'))
{
	// Builds the page control based on an existing page control id
	$editing_control = PageControlLogic::buildControlByPageControlId($page_control_id);
}
else
{ // New Control
	if (!Page::isPostBack()) {
		if ($control_id	= Page::get('controltype'))
		{

			//$new_control			= new PageControlStruct();
			$page_control->control_id	= $control_id;
			$page_control->page_id		= Page::get('pageid');
			$page_control->placeholder	= Page::get('placeholder');
			$page_control->sortorder	= PageControlLogic::getNextSortOrder($page_control->page_id, $page_control->placeholder);
		}
	}
	$editing_control		= PageControlLogic::buildNewControl($page_control);
}

if ($page_struct = PageLogic::getPageById($editing_control->getPageId())) {
	$lbl_page_description = Page::getControlById('lbl_page_description');
	$lbl_page_description->setText('<p>URL: ' . $page_struct->page_url . '</p>');
	Page::setTitle(Page::getTitle() . ' for ' . $page_struct->title);
}

$set_edit_mode = Page::get('editmode', 'Admin');
$editing_control->setEditMode($set_edit_mode);

$set_window_mode = 'Admin';
$editing_control->setWindowMode($set_window_mode);

/**
 *	Calling $editing_control->render() saves
 *	the attached control outputs the updated
 *	display variables
 */


ob_start();
eval(' ?> '.$editing_control->render() . '<?php ');
$editing_control_rendered = ob_get_contents();
$replace_match = 'edit.html?pagecontrolid=' . Page::get('pagecontrolid') .'&editmode=$1';
$editing_control_rendered = preg_replace("/ChangeScreen\((.+?)\)/", $replace_match, $editing_control_rendered);

ob_end_clean();

/**
 *	If there weren'te any errors outputted by the $editing_control then process the page_control
 */

if (Page::isPostBack())
{
	if (!ErrorManager::anyDisplayErrors())
	{

		$config_values = new ConfigDictionary();
		if ($editing_control instanceof EditableControl) {
//			$config_values->mergeDictionary($editing_control->getConfig());

			if ($xml_config = $editing_control->getConfigFile()) {
				//if ($auto_configurables = $xml_config->getPath('config/configurables/configurable[@type=Auto]')) {
				if ($auto_configurables = $xml_config->getPath('/config/configurables/configurable')) {

					foreach($auto_configurables as $auto_configurable) {
						$var = $auto_configurable->getParam('var');

						if ($auto_configurable->getParam('type') == 'Auto') {
							$config_values->set($var, $editing_control->getConfigValue($var));
						} else {
							if ($post_config = Page::get('configurable')) {
								$post_key = $editing_control->_getClassFileKey();
								if (isset($post_config[$post_key])) {
									if (isset($post_config[$post_key][$var])) {
										$config_values[$var] = $post_config[$post_key][$var];

										$editing_control->setConfigValue($var, $post_config[$post_key][$var]);
									}
								}
							}
						}
	
					}
				}
			}
		}

		$page_control->config = $config_values->toString();

		$page_control = PageControlLogic::save($page_control);
		
		FrameworkManager::loadLogic('page');
		$page_obj = PageLogic::getPageById($page_control->page_id);

		$forward_path = PathManager::getAdminContentPath($page_obj->page_url);
		Page::redirect($forward_path);
	}
}
else
{
	if (!empty($page_control_id)) $page_control = PageControlLogic::getPageControlById($page_control_id);
}

$editing_control_configuration = '';
//echo '<pre>';print_r($editing_control);echo '<hr />' . __FILE__ .':'.__LINE__;exit;
if ($editing_control instanceof EditableControl) {
	ob_start();
	$editing_control->_futureGetConfiguration();
	$editing_control_configuration = ob_get_contents();
	ob_end_clean();
} else if ($editing_control instanceof PageControlControl) {

} else {
	$editing_control_rendered = 'This type of control cannot be edited.  Click Save/Continue to continue.';
}

Page::setStruct('pagecontrol', $page_control);

?>