<?php

FrameworkManager::loadLibrary('string.urlmanipulator');
FrameworkManager::loadLogic('membership');

$membership_id = Page::get('membershipid');

$return_path = Page::get('returnpath', 'detail.html');
$return_path_name = Page::get('returnpathname', 'Memberships');

if ($membership_id) {
	if (!$membership = Membership::getUser($membership_id)) Page::redirect('index.html');

	$roles = Roles::getRolesForUser($membership);
}

if (Page::isPostBack()) {
	$username = Page::get('username');
	$password = Page::get('password');
	$email = Page::get('email');
	$message = '';
		
	if (empty($username)) ErrorManager::addError('Username is required');
	
	if ($membership_id) { // Existing
	
		if (!ErrorManager::anyDisplayErrors()) {
			
			$membership->setUsername($username);
			$membership->setEmail($email);
			
			$message = 'Username';
			
			if (!empty($password)) {
				$membership->changePassword($password);
				$message = ' and password';
			}
			
			$message .= ' updated';
			
			// Update membership
			Membership::updateUser($membership);
			Page::set('password', '');
			
		}
	} else { // New user
		FrameworkManager::loadDAO('membership');
		$membership_struct = new MembershipStruct();
		$membership_struct->enable = 1;
		$membership_struct->username = $username;
		$membership_struct->email = $email;
		$membership_struct->password = $password;
		
		if (empty($password)) NotificationManager::addMessage('The user account was created, but will not be active until it has been assigned a password.');
		
		if (!ErrorManager::anyDisplayErrors()) {
			$membership = Membership::createUser($membership_struct);
			Page::set('membershipid', $membership->getId());
			Page::set('password', '');
			NotificationManager::addMessage('User successfully created.');
		}
	}
	
	if (!ErrorManager::anyDisplayErrors()) {
		
		$return_path = CWI_STRING_UrlManipulator::appendUrl($return_path, 'membershipid', $membership->getId());
		
		if (!empty($message)) $return_path = CWI_STRING_UrlManipulator::appendUrl($return_path, 'message', $message);
		
		Page::redirect($return_path);
	}
	
} else {
	if ($membership_id) {
		$username = $membership->getUsername();
		$email = $membership->getEmail();
		Page::set('username', $username);
		Page::set('email', $email);
	}
	
}

if ($back_link = Page::getControlById('back_link')) {
	$back_link->setText($return_path);
}

if ($back_link_name = Page::getControlById('back_link_name')) {
	$back_link_name->setText($return_path_name);
}

Page::set('returnpath', $return_path);
Page::set('returnpathname', $return_path_name);

?>