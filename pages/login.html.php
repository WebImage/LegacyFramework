<?php

Page::setTitle( ConfigurationManager::get('SITE_NAME') . ' Login' );

if (Page::isPostBack()) {
	#$login = Page::getStruct('membership');
	#$username = $login->username;
	#$password = $login->password;
	$username = Page::get('username');
	$password = Page::get('password');
	
	if ($membership = Membership::validateUser($username, $password)) {
		$goto = ConfigurationManager::get('DIR_WS_HOME') . 'account/';
		
		if ($return_path = Page::get('returnpath')) {
			$goto = $return_path;
		} else if ($return_path = SessionManager::get('returnpath')) {
			$goto = $return_path;
			SessionManager::del('returnpath');
		} else {
			if ($start_page = Roles::getStartPage()) $goto = $start_page;			
			else if (Roles::isUserInRole('AdmBase')) $goto = ConfigurationManager::get('DIR_WS_ADMIN');
		}
		
		Page::redirect($goto);
	} else {
		ErrorManager::addError('Invalid username/password credentials');
	}
} else if (Page::get('logout')) {
	NotificationManager::addMessage('You have been successfully logged out.');
	Membership::logOut();
}

?>
