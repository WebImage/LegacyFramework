<?php

if ($page_control_id = Page::get('pagecontrolid')) {
	FrameworkManager::loadLogic('pagecontrol');
	PageControlLogic::delete($page_control_id);
}

?>