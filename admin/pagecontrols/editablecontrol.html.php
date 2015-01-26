<?php

#FrameworkManager::loadLogic('page');
FrameworkManager::loadLogic('pagecontrol');
FrameworkManager::loadStruct('pagecontrol');
#$page_control = Page::getStruct('pagecontrol');

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

			$page_control			= new PageControlStruct();
			$page_control->control_id	= $control_id;
			$page_control->page_id		= Page::get('pageid');
			$page_control->placeholder	= Page::get('placeholder');
			$page_control->sortorder	= PageControlLogic::getNextSortOrder($page_control->page_id, $page_control->placeholder);
		}
	}
	$editing_control		= PageControlLogic::buildNewControl($page_control);

}

$set_edit_mode = Page::get('editmode', 'Admin');
$editing_control->setEditMode($set_edit_mode);

$set_window_mode = Page::get('windowmode', 'Inline');
$editing_control->setWindowMode($set_window_mode);

interface TestClassA {}
class TestClassB implements TestClassA{}

if (is_subclass_of('TestClassB', 'TestClassA')) echo 'true'; else echo 'false';

echo '<form id="ph_left_-form"><input type="hidden" name="pagecontrol" value="" />
<input type="hidden" name="pageid" value="5" />
<input type="hidden" name="placeholder" value="ph_left" />
<input type="hidden" name="controltype" value="7" />
<input type="hidden" name="editmode" value="Admin" />
<input type="hidden" name="windowmode" value="Admin" />
<input type="text" name="header" />
<textarea name="description"></textarea>
<input type="submit" value="Submit" />
</form>';
exit;

echo 'Output: ' . $editing_control->render();exit;

ob_start();
eval(' ?> '.$editing_control->render() . '<?php ');
$editing_control_rendered = ob_get_contents();
$replace_match = 'edit.html?pagecontrolid=' . Page::get('pagecontrolid') .'&editmode=$1';
$editing_control_rendered = preg_replace("/ChangeScreen\((.+?)\)/", $replace_match, $editing_control_rendered);

ob_end_clean();

/**
 *	If there weren'te any errors outputted by the $editing_control then process the page_control
 */

if (Page::isPostBack()){
	#if ($editing_control->handlePostBack()) {
	#	die("TRUE");
	#}
	#die("SAVE");
	if (!ErrorManager::anyDisplayErrors())
	{
		
		$config_values = array();
		if (is_a($editing_control, 'EditableControl')) {
			if ($xml_config = $editing_control->getConfigFile())
			{
				//if ($auto_configurables = $xml_config->getPath('config/configurables/configurable[@type=Auto]')) {
				if ($auto_configurables = $xml_config->getPath('/config/configurables/configurable')) {
					foreach($auto_configurables as $auto_configurable)
					{
						$var = $auto_configurable->getParam('var');
						$obj_var = 'm_' . $var;
						if ($auto_configurable->getParam('type') == 'Auto') {
							if (isset($editing_control->$obj_var))
							{
								$config_values[$var] = $editing_control->$obj_var;
							}
						} else {
							if ($post_config = Page::get('configurable')) {
								$post_key = $editing_control->_getClassFileKey();
								if (isset($post_config[$post_key])) {
									if (isset($post_config[$post_key][$var])) {
										$config_values[$var] = $post_config[$post_key][$var];
										$editing_control->$obj_var = $post_config[$post_key][$var];
									}
								}
							}
						}
	
					}
				}
			}
		}

		$config_string = Control::BuildConfigString($config_values);
		$page_control->config = $config_string;

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
if (is_a($editing_control, 'EditableControl')) {
	ob_start();
	$editing_control->_futureGetConfiguration();
	$editing_control_configuration = ob_get_contents();
	ob_end_clean();
} else {
	$editing_control_rendered = 'This type of control cannot be edited.  Click Save/Continue to continue.';
}

Page::setStruct('pagecontrol', $page_control);

?>