<?php

FrameworkManager::loadDAO('membership');

class MembershipLogic {
	
	public static function getMembershipById($membership_id) {
		$membership_dao = new MembershipDAO();
		return $membership_dao->load($membership_id);
	}
	
	public static function getMembershipByIds(array $membership_ids) {
		$membership_dao = new MembershipDAO();
		return $membership_dao->getMembershipByIds($membership_ids);
	}
	
	public static function save($membership_struct) {
		$membership_dao = new MembershipDAO();
		return $membership_dao->save($membership_struct);
	}
	
	public static function getNumMemberships() {
		$membership_dao = new MembershipDAO();
		return $membership_dao->getNumMemberships();
	}
		
	public static function getNumMembershipsByUsername($username) {
		$membership_dao = new MembershipDAO();
		$membership_dao->setCacheResults(false);
		return $membership_dao->getNumMembershipsByUsername($username);
	}
	
	public static function getNumMembershipsByEmail($email) {
		$membership_dao = new MembershipDAO();
		$membership_dao->setCacheResults(false);
		return $membership_dao->getNumMembershipsByEmail($email);
	}
	
	public static function searchMemberships($username=null, $email=null, $keyword=null, $current_page=null, $results_per_page=null) {
		if (!empty($current_page) && empty($results_per_page)) $results_per_page = 10;
		$dao_membership = new MembershipDAO();
		return $dao_membership->searchMemberships($username, $email, $keyword, $current_page, $results_per_page);
	}
	
	public static function searchMembershipsByKeyword($keyword=null, $current_page=null, $results_per_page=null) {
		return MembershipLogic::searchMemberships(null, null, $keyword, $current_page, $results_per_page);
	}
	
	public static function getParametersByMembershipId($membership_id) {
		FrameworkManager::loadDAO('membershipparameter');
		$membership_parameter_dao = new MembershipParameterDAO();
		return $membership_parameter_dao->getParametersByMembershipId($membership_id);
	}
	
	public static function getMembershipByUsernameOrEmail($username_or_email) {
		$dao_membership = new MembershipDAO();
		return $dao_membership->getMembershipByUsernameOrEmail($username_or_email);
	}
	
	public static function getMembershipByCentralLoginId($central_login_id) {
		$dao_membership = new MembershipDAO();
		return $dao_membership->getMembershipByCentralLoginId($central_login_id);
	}
	
	public static function getMembershipsByUsernameOrEmail($username_or_email) {
		$dao_membership = new MembershipDAO();
		return $dao_membership->getMembershipsByUsernameOrEmail($username_or_email);
	}
	
	public static function getParameterByMembershipIdAndParameter($membership_id, $parameter) {
		FrameworkManager::loadDAO('membershipparameter');
		$membership_parameter_dao = new MembershipParameterDAO();
		return $membership_parameter_dao->getParameterByMembershipIdAndParameter($membership_id, $parameter);
	}
	
	public static function saveParameter($parameter_struct) {
		FrameworkManager::loadDAO('membershipparameter');
		$membership_parameter_dao = new MembershipParameterDAO();
		return $membership_parameter_dao->save($parameter_struct);
	}
	/** 
	 * Inserts a parameter into the membership_parameter table without checking whether the value already exists 
	 * The only use case at the time of this documentation was for the importation of configuration settings for external systems (Drupal in particular)
	 **/
	public static function insertParameter($membership_id, $parameter, $value) {
		FrameworkManager::loadDAO('membershipparameter');
		FrameworkManager::loadStruct('membershipparameter');
		$parameter_struct = new MembershipParameterStruct();
		$parameter_struct->membership_id = $membership_id;
		$parameter_struct->parameter = $parameter;
		$parameter_struct->value = $value;
		
		$membership_parameter_dao = new MembershipParameterDAO();
		$membership_parameter_dao->setForceInsert(true);
		return $membership_parameter_dao->save($parameter_struct);
	}
}

?>