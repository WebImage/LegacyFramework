<?php

FrameworkManager::loadLogic('role');

$dg_roles = Page::getControlById('dg_roles');
$dg_roles->setData( RoleLogic::getVisibleRoles() );

?>