<?php

FrameworkManager::loadLibrary('class.scanner.classscanner');
FrameworkManager::loadLibrary('xml.compile');
FrameworkManager::loadLogic('control');
FrameworkManager::loadStruct('control');

$rs_controls = ControlLogic::autoAddDiscoveredControls();

if ($dg1 = Page::getControlById('dg1')) {
	$dg1->setData($rs_controls);
}


?>