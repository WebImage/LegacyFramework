<?php

/**
 * 05/26/2009	(Robert Jones) Added functionality within getUser() so that it can be used to retrieve any user, not just the currently logged in user
 * 08/11/2009	(Robert Jones) Changed login procedure to check usernamd and email
 * 02/10/2010	(Robert Jones) Finally implemented getUsers()
 * 04/06/2010	(Robert Jones) Modified getUsers() to check for usernames and emails
 */

class SqlMembershipProvider extends MembershipProvider {

	function _generateVerifyKey() {
		$verify_key = '';
		for ($i=0; $i < 20; $i++) {
			$verify_key .= chr(rand(48, 90));
		}
		return $verify_key;
	}
	
	function createUser($membership_struct) {
		parent::createUser($membership_struct);
		FrameworkManager::loadDAO('membership');
		$membership_dao = new MembershipDAO();

		$membership_struct->login_key = self::_getLoginKeyFromMembershipStruct($membership_struct);
		
		if (strlen($membership_struct->enable) == 0) $membership_struct->enable = 1;
		
		if (!empty($membership_struct->password)) {
			$membership_struct->verify_key = self::_generateVerifyKey();
			$membership_struct->password = md5($membership_struct->verify_key . $membership_struct->password);	
		}

		$created_user = $membership_dao->save($membership_struct);
		$membership_user = self::getMembershipUserFromMembershipStruct($created_user);

		return $membership_user;
	} // Creates a new user.

	function createUserAndLogin($membership_struct) {
		$membership_user = self::createUser($membership_struct);
		self::_initSession($membership_user);
		return $membership_user;
	}
	
	function getUser($user_id=null) {
		
		if (is_object($user_id)) return $user_id;
		
		$use_loggedin_user = (is_null($user_id));

		FrameworkManager::loadDAO('membership');
		
		if ($use_loggedin_user) {
			
			$user_id = SessionManager::get('my1');
			$login_key = SessionManager::get('lk');
			
			if ($user_id === false) return false;
			if ($login_key === false) return false;
			
		} else {
			
			$login_key = 'NotSet';
			
		}
		
		$membership_dao = new MembershipDAO();
		if ($membership = $membership_dao->load($user_id)) {
			
			// Verify that session login key matches the last login_key generated (created when a member logs in)
			if ($use_loggedin_user) {
				if ($membership->enable != 1) return false;
				if ($login_key != $membership->login_key) return false;
			}
			
			// Call current class (or overriding class) method - not sure this is the best way to override static methods, but it's all that could be figured out at the time this was written
			return call_user_func(array(get_called_class(), 'getMembershipUserFromMembershipStruct'), $membership);

			#return self::getMembershipUserFromMembershipStruct($membership);
		} else {

			return false;
			
		}
	}
	
	function loginAs($user_id) {
		
		if ($return_membership = self::getUser($user_id)) {
			
			self::_initSession($return_membership);
			
			return $return_membership;
		}
		
		return false;
	}
	
	function deleteUser($user) {} // Deletes a user.
	
	// Updates a user with new information.
	function updateUser($membership_user_obj) { // MembershipUser
		$membership_struct = self::_getMembershipStructFromMembershipUser($membership_user_obj);
		
		FrameworkManager::loadDAO('membership');
		$membership_dao = new MembershipDAO();

		if ($membership_user_obj->isPasswordChanged()) {
			$password = $membership_user_obj->getPassword();
			if (!empty($password)) {
				$membership_struct->verify_key = self::_generateVerifyKey();
				$membership_struct->password = md5($membership_struct->verify_key . $membership_struct->password);	
			}
		} else {
			$membership_struct->password = null; // Make sure password doesn't get updated.
		}
		
		$updated_user = $membership_dao->save($membership_struct);
		$membership_user = self::getMembershipUserFromMembershipStruct($updated_user);

		return $membership_user;

	}
	
	function getUsers($email_or_username) { // Returns a list of users.  OR Searches for users by username or e-mail address.
		FrameworkManager::loadLogic('membership');
		$return_results = array();
		//$found_members = MembershipLogic::searchMemberships($username);
		$found_members = MembershipLogic::getMembershipsByUsernameOrEmail($email_or_username);
		while ($member_struct = $found_members->getNext()) {
			array_push($return_results, self::getMembershipUserFromMembershipStruct($member_struct));
		}
		return $return_results;
	}
	function findUserByName($name) {} // Finds a user by name or e-mail.
	function findUserByEmail($email) {}
	
	function validateUser($username, $password)  // Validates (authenticates) a user.
	{
		FrameworkManager::loadDAO('membership');
		$membership_dao = new MembershipDAO();

		if (!$membership = $membership_dao->getMembershipByUsernameOrEmail($username)) return false;
		
		// Do not allow users without a password to login:
		if (strlen($membership->password) == 0) return false;
		
		/*
		 * If verify_key is set, use it as the salt to find the password
		 * Otherwise, check to see if the password matched the plain text stored password, or the stored password with md5
		 **/

		$use_verify_key = !empty($membership->verify_key);
		$valid = false;
		if ($use_verify_key && (md5($membership->verify_key . $password) == $membership->password)) $valid = true;
		else {
			if ($password == $membership->password) $valid = true;
			else if (md5($password) == $membership->password) $valid = true;
		}
		
		#if ( (!empty($membership->verify_key)) ? (md5($membership->verify_key . $password) == $membership->password) : ($password == $membership->password || md5($password) == $membership->password) ) {
		if ($valid) {
			
			$membership->login_key = self::_getLoginKeyFromMembershipStruct($membership);

			$return_membership = self::getMembershipUserFromMembershipStruct($membership);
			
			// Save Login Key
			$membership_dao->save($membership);
			
			self::_initSession($return_membership);
			
			return $return_membership;
		}
	
		//Shouldn't ever get here, but just in case:
		return false;
	}
	
	function _initSession($membership_user) {
		if (!empty($membership_user)) {
			// Login ID
			SessionManager::set('my1', $membership_user->getId());
			// Login Key
			SessionManager::set('lk', $membership_user->getLoginKey());
		} else return false;
	}
	
	function _getLoginKeyFromMembershipStruct($membership_struct) {
		$login_key = $_SERVER['REMOTE_ADDR'] . date('Ymd') . $membership_struct->id;
		$login_key = md5($login_key);
		return $login_key;
	}
	
	
	function _getMembershipStructFromMembershipUser($membership_user) {
		FrameworkManager::loadStruct('membership');
		$membership_struct = new MembershipStruct();

		#$membership_struct->approved			= $membership_user->getApproved();
		#$membership_struct->approved_by		= $membership_user->getApprovedBy();
		$membership_struct->comment			= $membership_user->getComment();
		$membership_struct->email			= $membership_user->getEmail();
		$membership_struct->enable			= ($membership_user->isEnabled()) ? 1 : 0;
		$membership_struct->failed_login_attempts	= $membership_user->getFailedLoginAttempts();
		$membership_struct->id				= $membership_user->getId();
		$membership_struct->last_activity		= $membership_user->getLastActivity();
		$membership_struct->last_login			= $membership_user->getLastLogin();
		$membership_struct->last_password_changed	= $membership_user->getLastPasswordChanged();
		$membership_struct->login_key			= $membership_user->getLoginKey();
		$membership_struct->password			= $membership_user->getPassword();
		$membership_struct->password_answer		= $membership_user->getPasswordAnswer();
		$membership_struct->password_question		= $membership_user->getPasswordQuestion();
		$membership_struct->username			= $membership_user->getUsername();
		#$membership_struct->verify_key			= $membership_user->getVerifyKey();
		$membership_struct->visitor_id			= $membership_user->getVisitorId();
		return $membership_struct;
	}
	
	public static function _getMembershipUserFromMembershipStruct($membership_struct) { // kept in case other classes rely on it for backwards compatability
		return self::getMembershipUserFromMembershipStruct($membership_struct);
	}
	
	public static function getMembershipUserFromMembershipStruct($membership_struct) {
		$return_membership = new MembershipUser(
			$membership_struct->approved, 
			$membership_struct->approved_by, 
			$membership_struct->comment, 
			$membership_struct->created, 
			$membership_struct->created_by, 
			$membership_struct->email, 
			($membership_struct->enable == 1) ? true:false, 
			$membership_struct->failed_login_attempts, 
			$membership_struct->id, 
			$membership_struct->last_activity, 
			$membership_struct->last_login, 
			$membership_struct->last_password_changed, 
			$membership_struct->login_key,
			'', /* Removed this because there is no reason to display $membership_struct->password, */
			$membership_struct->password_answer, 
			$membership_struct->password_question, 
			$membership_struct->username, 
			$membership_struct->visitor_id
			);
		return $return_membership;
	}
	
	function getUsersByOnline() {} // Gets the number of users online
	
	function logOut() {
		SessionManager::del('my1');
		SessionManager::del('lk');
	}
	
	function getParameter($parameter, $user_id=null) {
		if ($user = self::getUser($user_id)) {
			FrameworkManager::loadLogic('membership');
			if ($parameter = MembershipLogic::getParameterByMembershipIdAndParameter($user->getId(), $parameter)) {
				return $parameter->value;
			} else {
				return false;
			}
		} else {
			return false;
		}
	}
	
	function setParameter($parameter, $value, $user_id=null) {
		if ($user = self::getUser($user_id)) {
			FrameworkManager::loadLogic('membership');
			
			if ($parameter_struct = MembershipLogic::getParameterByMembershipIdAndParameter($user->getId(), $parameter)) {
				$parameter_struct->value = $value;
			} else {
				FrameworkManager::loadStruct('membershipparameter');
				$parameter_struct = new MembershipParameterStruct();
				$parameter_struct->membership_id = $user->getId();
				$parameter_struct->parameter = $parameter;
				$parameter_struct->value = $value;
			}

			return MembershipLogic::saveParameter($parameter_struct);
		} else {
			return false;
		}
	}
	
}

?>