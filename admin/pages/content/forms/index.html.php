<?php

FrameworkManager::loadLogic('form');

$rs_forms = FormLogic::getForms();

if ($dg_forms = Page::getControlById('dg_forms')) {
	
	$dg_forms->setData($rs_forms);
	
}

?>