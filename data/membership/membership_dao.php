<?php
/**
 * DataAccessObject for Memberships
 * 
 * @author Robert Jones II <support@corporatewebimage.com>
 * @copyright Copyright (c) 2007 Corporate Web Image, Inc.
 * @package DataAccessObject
 * @version 1.0 (10/01/2007), Athena_v1.0
 */

// Load required data structures
FrameworkManager::loadStruct('membership');

class MembershipDAO extends DataAccessObject {
	var $modelName = 'MembershipStruct';
	//var $tableName = TABLE_CONTENT;
	var $primaryKey = 'id';
	var $updateFields = array('approved', 'approved_by', 'central_login_id', 'comment', 'created', 'created_by', 'email', 'enable', 'failed_login_attempts', 'last_login', 'last_password_changed', 'login_key', 'password', 'password_answer', 'password_question', 'updated', 'updated_by', 'username', 'verify_key', 'visitor_id');
		
	function MembershipDAO() {
		$this->tableName = DatabaseManager::getTable('memberships');
	}
	
	function getMembershipByIds(array $membership_ids) {
		
		$select_sql = "
			SELECT *
			FROM " . $this->tableName . "
			WHERE
				id IN ('" . $this->safeString($membership_ids) . "')";
				
		return $this->selectQuery($select_sql, $this->modelName);
	}
	
	/*
	function createUser($username, $password, $email, $enable, $created_by, $approved_by, $password_question, $password_answer) {
		$membership = new MembershipStruct();
		
		if (!empty($approved_by)) $approved = date('Y-m-d H:i:s');
		else $approved = null;
		
		$membership->approved			= $approved;
		$membership->approved_by		= $approved_by;
		$membership->created			= date('Y-m-d H:i:s');
		$membership->created_by			= $created_by;
		$membership->email			= $email;
		$membership->failed_login_attempts	= 0;
		$membership->last_login_date		= null;
		$membership->last_password_changed_date	= null;
		$membership->password			= $password;
		$membership->password_answer		= $password_answer;
		$membership->password_question		= $password_question;
		$membership->visitor_id			= 0;
		$membership->username			= $username;
		return $this->save($membership);
	}
	*/
	
	function getNumMemberships() {
		$select_sql = "
			SELECT COUNT(*) AS total
			FROM `" . $this->tableName . "`";
		$query = $this->selectQuery($select_sql);
		$object = $query->getAt(0);
		return $object->total;
	}
	
	function getNumMembershipsByUsername($username) {
		$select_sql = "
			SELECT COUNT(*) AS total
			FROM " . $this->tableName . "
			WHERE 
				username = '" . $this->safeString($username) . "'";
		$query = $this->selectQuery($select_sql);
		$object = $query->getAt(0);
		return $object->total;
	}
	
	function getNumMembershipsByEmail($email) {
		$select_sql = "
			SELECT COUNT(*) AS total
			FROM " . $this->tableName . "
			WHERE 
				email = '" . $this->safeString($email) . "'";
		$query = $this->selectQuery($select_sql);
		$object = $query->getAt(0);
		return $object->total;
	}
	
	function getMembershipByUsername($username) {
		$select_sql = "
			SELECT *
			FROM " . $this->tableName . "
			WHERE
				enable = 1 AND 
				username = '" . $this->safeString($username) . "'";
		$query = $this->selectQuery($select_sql);
		return $query->getAt(0);
	}
	
	function getMembershipsByUsernameOrEmail($email_or_users) {
		$select_sql = "
			SELECT *
			FROM " . $this->tableName . "
			WHERE
				enable = 1 AND 
				(username = '" . $this->safeString($email_or_users) . "' OR email = '" . $this->safeString($email_or_users) . "')";
		return $this->selectQuery($select_sql, $this->modelName);
	}
	
	function getMembershipByUsernameOrEmail($email_or_users) {
		$query = $this->getMembershipsByUsernameOrEmail($email_or_users);
		return $query->getAt(0);
	}
	
	/*function validateUser($username, $password) {
		$select_sql = "
			SELECT *
			FROM " . $this->tableName . "
			WHERE 
				username = '" . $username . "' AND
				password = '" . $password . "'";
		return $this->selectQuery($select_sql, $this->modelName);
	}*/
	
	function getMembershipByCentralLoginId($central_login_id) {
		$select_sql = "
			SELECT *
			FROM " . $this->tableName . "
			WHERE
				central_login_id = '" . $this->safeString($central_login_id) . "'";
		$query = $this->selectQuery($select_sql);
		return $query->getAt(0);
	}
	
	function searchMemberships($username=null, $email=null, $keyword=null, $current_page=null, $results_per_page=null) {
		FrameworkManager::loadLibrary('db.daosearch');
		$search = new DAOSearch('memberships', $current_page, $results_per_page);
		
		if (!empty($username)) {
			$username_field = new DAOSearchField('memberships', 'username', $username);
			$search->addSearchField($username_field);
		}
		if (!empty($email)) {
			$email_field = new DAOSearchField('memberships', 'email', $email);
			$search->addSearchField($email_field);
		}
		
		if (!empty($keyword)) {
			$group = new DAOSearchOrGroup();
			
			$username_field = new DAOSearchFieldWildcard('memberships', 'username', $keyword);
			$group->addSearchField($username_field);
			
			$email_field = new DAOSearchFieldWildcard('memberships', 'email', $keyword);
			$group->addSearchField($email_field);
			
			$search->addSearchField($group);
		}
		return $this->search($search);
	}
	
}

?>