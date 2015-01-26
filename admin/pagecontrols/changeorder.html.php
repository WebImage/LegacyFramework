<?php

if ($page_control_id = Page::get('pagecontrolid')) {
	$direction = Page::get('direction'); // up | down
	
	FrameworkManager::loadLogic('pagecontrol');
	switch (strtolower($direction)) {
		case 'up':
			PageControlLogic::moveUp($page_control_id);
			break;
		case 'down':
			PageControlLogic::moveDown($page_control_id);
			break;
	}
} else die("INVALID PAGECONTROLID");

?>