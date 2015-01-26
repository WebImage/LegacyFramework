<?php

class MembershipUser {
	private $changePassword = false;
	var $m_approved;
	var $m_approvedBy;
	var $m_comment;
	var $m_created;
	var $m_createdBy;
	var $m_email;
	var $m_enable;
	var $m_failedLoginAttempts;
	var $m_id;
	var $m_lastActivity;
	var $m_lastLogin;
	var $m_lastPasswordChanged;
	var $m_loginKey;
	var $m_password;
	var $m_passwordAnswer;
	var $m_passwordQuestion;
	var $m_siteId;
	var $m_username;
	var $m_visitorId;
	
	//var $m_providerName;
	
	function MembershipUser($approved, $approved_by, $comment, $created, $created_by, $email, $enable, $failed_login_attempts, $id, $last_activity, $last_login, $last_password_changed, $login_key, $password, $password_answer, $password_question, $username, $visitor_id) {
		$this->m_approved		= $approved;
		$this->m_approvedBy		= $approved_by;
		$this->m_comment		= $comment;
		$this->m_created		= $created;
		$this->m_createdBy		= $created_by;
		$this->m_email			= $email;
		$this->m_enable			= $enable;
		$this->m_failedLoginAttempts	= $failed_login_attempts;
		$this->m_id			= $id;
		$this->m_lastActivity		= $last_activity;
		$this->m_lastLogin		= $last_login;
		$this->m_lastPasswordChanged	= $last_password_changed;
		$this->m_loginKey		= $login_key;
		$this->m_password		= $password;
		$this->m_passwordAnswer		= $password_answer;
		$this->m_passwordQuestion	= $password_question;
		$this->m_username		= $username;
		$this->m_visitorId		= $visitor_id;
	}
	
	function getUsername() { return $this->m_username; }
	//function getProviderUserKey() { return $this->m_providerUserKey; }
	
	function getId() { return $this->m_id; }
	function getEmail() { return $this->m_email; }
	function getPasswordAnswer() { return $this->m_passwordAnswer; }
	function getPasswordQuestion() { return $this->m_passwordQuestion; }
	function getComment() { return $this->m_comment; }
	#function getLastLockoutDate() { return $this->m_lastLockoutDate; }
	function getLoginKey() { return $this->m_loginKey; }
	function getCreateDate() { return $this->m_created; }
	function getLastLogin() { return $this->m_lastLogin; }
	function getLastActivity() { return $this->m_lastActivity; }
	function getLastPasswordChanged() { return $this->m_lastPasswordChanged; }
	function getProviderName() { return $this->m_providerName; }
	function getApproved() { return $this->m_approved; }
	function getApprovedBy() { return $this->m_approvedBy; }
	function getFailedLoginAttempts() { return $this->m_failedLoginAttempts; }
	function getSiteId() { return $this->m_siteId; }
	function getVisitorId() { return $this->m_visitorId; }
	
	function setUsername($username) { $this->m_username = $username; }
	function setProviderUserKey($provider_user_key) { $this->m_providerUserKey = $provider_user_key; }
	function setEmail($email) { $this->m_email = $email; }
	function setPasswordAnswer($password_answer) { $this->m_passwordAnswer = $password_answer; }
	function setPasswordQuestion($password_question) { $this->m_passwordQuestion = $password_question; }
	function setComment($comment) { $this->m_comment = $comment; }
	function setLastLockoutDate($last_lockout_date) { $this->m_lastLockoutDate = $last_lockout_date; }
	function setCreateDate($create_date) { $this->m_createDate = $create_date; }
	function setLastLogin($last_login) { $this->m_lastLogin = $last_login; }
	function setLastActivity($last_activity) { $this->m_lastActivity = $last_activity; }
	function setLastPasswordChanged($last_password_changed) { $this->m_lastPasswordChanged = $last_password_changed; }
	function setProviderName($provider_name) { $this->m_providerName = $provider_name; }
	function setApproved($is_approved) { $this->m_isApproved = $is_approved; }
	function setApprovedBy($approved_by) { $this->m_approvedBy = $approved; }
	function setIsLockedOut($is_locked_out) { $this->m_isLockedOut = $is_locked_out; }
	function setFailedLoginAttempts($failed_login_attempts) { $this->m_failedLoginAttempts = $failed_login_attempts; }
	function setVisitorId($visitor_id) { $this->m_visitorId = $visitor_id; }
	
	function isEnabled($is_enabled=null) {
		if (is_null($is_enabled)) {
			return $this->m_enable;
		} else {
			$this->m_enable = $is_enabled;
		}
	}
	
	function isApproved($is_approved=null) {
		if (is_null($is_approved)) {
			return $this->m_isApproved;
		} else {
			$this->setApproved($is_approved);
		}
	}
	function isLockedOut($lock_out=null) {
		if (is_null($lock_out)) {
			return $this->m_isLockedOut;
		} else {
			$this->setIsLockedOut($lock_out);
		}
	}
	
	function getIsOnline() {}
	
	function getPassword() { return $this->m_password; }
	function changePassword($password) {
		$this->changePassword = true;
		$this->m_password = $password;
	}
	function isPasswordChanged() { return $this->changePassword; }
}

?>