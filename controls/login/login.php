<?php
/**
 * Changelog
 * 06/08/2010	Added the ability to customize the submit button
 */
class LoginControl extends WebControl {
	
	// Create User
	var $m_displayCreateUser	= false;
	var $m_createUserText		= 'Create new account';
	var $m_createUserUrl		= '/createaccount.html';
	
	// Remember me
	var $m_displayRememberMe	= false; // Not implemented
	var $m_rememberText		= 'Remember me';
	
	// Password recovery
	var $m_displayPasswordRecover	= false;
	var $m_passwordRecoveryText	= 'Forgotten your password?';
	var $m_passwordRecoveryUrl	= '/recoverpassword.html';
	
	// Login Text
	var $m_loginButtonText		= 'Login';
	var $m_loginButtonImage	= '';
	var $m_loginButtonImageWidth	= '';
	var $m_loginButtonImageHeight	= '';
	
	var $m_titleText		= 'Please log in';
	var $m_usernameText		= 'Username: <br />';
	var $m_passwordText		= 'Password: <br />';
	
	// Form Info
	var $m_formAction		= null;
	
	function __construct($init_array=array()) {
		if (empty($this->m_formAction)) $this->m_formAction = ConfigurationManager::get('DIR_WS_HOME') . 'login.html';
		parent::__construct($init_array);
	}
	
	function prepareContent() {
		$form_html = '<form method="post" action="' . $this->m_formAction . '">';
		$form_html .= '<strong>'.$this->m_titleText .'</strong><br />';
		$form_html .= $this->m_usernameText . '<input type="text" name="username" value="' . Page::get('username') . '" /><br />';
		$form_html .= $this->m_passwordText . '<input type="password" name="password" value="' . Page::get('password') . '" /><br />';
		if ($return_path = Page::get('returnpath')) {
			$form_html .= '<input type="hidden" name="returnpath" value="' . $return_path . '" />';
		}
		if (empty($this->m_loginButtonImage)) { // Standard form button
			$form_html .= '<input type="submit" value="'.$this->m_loginButtonText.'" />';
		} else {
			$image_html = '<input type="image" src="' . ConfigurationManager::getValueFromString($this->m_loginButtonImage) . '"';
			if (!empty($this->m_loginButtonImageWidth)) $image_html .= ' width="' . $this->m_loginButtonImageWidth . '"';
			if (!empty($this->m_loginButtonImageHeight)) $image_html .= ' height="' . $this->m_loginButtonImageHeight . '"';
			if (!empty($this->m_loginButtonText)) $image_html .= ' alt="' . $this->m_loginButtonText . '" style="margin-top:10px;"';
			$image_html .= ' />';
			$form_html .= $image_html;
		}
		
		$form_html .= '</form>';
		$this->setRenderedContent($form_html);
	}
}

?>