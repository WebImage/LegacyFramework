<?php

#FrameworkManager::loadLogic('config');
#$template = Page::getStruct('template');

if (Page::isPostBack()) {
	
	$site_name	= Page::get('sitename');
	$email		= Page::get('email');
	
	if (empty($site_name)) ErrorManager::addError('Site name is required');
	
	if (empty($email)) ErrorManager::addError('Email is required');
	else if (!preg_match('/.+@.+\..+/', $email)) ErrorManager::addError('Invalid email format, e.g. user@' . ConfigurationManager::get('DOMAIN'));
	
	if (!ErrorManager::anyDisplayErrors()) {
		
		CM::setAndPersist('SITE_NAME', $site_name);
		CM::setAndPersist('EMAIL', $email);
		
		NotificationManager::addMessage('Site settings have been updated');
		
	}
		
} else {
	
	Page::set('sitename', CM::get('SITE_NAME'));
	Page::set('email', CM::get('EMAIL'));
	
}

?>