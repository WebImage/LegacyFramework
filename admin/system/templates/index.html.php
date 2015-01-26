<?php

FrameworkManager::loadLogic('template');

$dl_templates = Page::getControlById('dl_templates');
$dl_templates->setData(TemplateLogic::getAllTemplates());

?>