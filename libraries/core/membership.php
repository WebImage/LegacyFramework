<?php

class Membership {
	// Common to all
	var $m_defaultProvider;
	var $m_providers = array();
	
	private $providers;
	
	// Membership specific
	var $m_loginUrl;
	var $m_requireSSL;
	var $m_slidingExpiration;
	
	private $isInitialized = false;
	
	// Common methods
	public static function getInstance() {
		$instance = Singleton::getInstance('Membership');
		if (!$instance->isInitialized) {
			#$instance->init();
			$instance->isInitialized = true;
			$instance->providers = new LazyProviderDictionary();
		}
		return $instance;
	}
	
	public static function addProvider($config) {
		$_this = Membership::getInstance();
		$name = $config->get('name');
		$_this->providers->set($name, new LazyProvider($config));
		return true;
		
		$class_name = $config->get('className');
		$provider = new $class_name;
		$provider->config = $config;
		$_this->m_providers[$name] = $provider;
		
		$provider->config = $config;
		$_this->m_providers[$name] = $provider;
	}
	
	public static function setDefaultProvider($provider) {
		$_this = Membership::getInstance();
		$_this->m_defaultProvider = $provider;
	}
	public static function getDefaultProvider() {
		$_this = Membership::getInstance();
		return $_this->m_defaultProvider;
	}
	public static function getProvider($provider_name=null) {
		
		$_this = Membership::getInstance();

		if (is_null($provider_name)) $provider_name = Membership::getDefaultProvider(); 

		if ($provider_wrapper = $_this->providers->get($provider_name)) {

			if ($provider = $provider_wrapper->getInitializedClass()) {

				return $provider;
				
			}
			
		}
		
		return false;
	}
	
	public static function getProviders() {
		$_this = Membership::getInstance();
		
		$providers = $_this->providers->getAll();
		$return = new ProviderDictionary();
		
		while ($provider = $providers->getNext()) {

			$return->set($provider->getKey(), $provider->getDefinition()->getInitializedClass());
			
		}
		
		return $return;
	}
	public static function isStarted() {
	}
	
	// Membership specific methods
	public static function createUser($membership_struct=null) { // Creates a new user.
		$provider = Membership::getProvider();
		return $provider->createUser($membership_struct);
	}
	public static function createUserAndLogin($membership_struct) {
		$provider = Membership::getProvider();
		return $provider->createUserAndLogin($membership_struct);
	}
	public static function deleteUser($membership_user_obj) { // Deletes a user.
		$provider = Membership::getProvider();
		return $provider->deleteUser($membership_user_obj);
	}
	public static function updateUser($membership_membership_user_obj) { // Updates a user with new information.
		$provider = Membership::getProvider();
		return $provider->updateUser($membership_membership_user_obj);
	}
	/**
	 * @return MembershipUser
	 **/
	public static function getMembershipUserFromMembershipStruct($membership_struct) {
		$provider = Membership::getProvider();
		return $provider->getMembershipUserFromMembershipStruct($membership_struct);
	}
	public static function getUser($user_id=null) { // Get user
		$provider = Membership::getProvider();
		return $provider->getUser($user_id);
	}
	public static function getUsers($email_or_username) { // Returns a list of users.  OR Searches for users by username or e-mail address.
		$provider = Membership::getProvider();
		return $provider->getUsers($email_or_username);
	}
	public static function findUserByName($name) { // Finds a user by name or e-mail.
		$provider = Membership::getProvider();
		return $provider->findUserByName($name);
	}
	public static function findUserByEmail($email) {
		$provider = Membership::getProvider();
		return $provider->findUserByEmail($email);
	}
	public static function loginAs($user_id) {
		$provider = Membership::getProvider();
		return $provider->loginAs($user_id);
	}
	public static function validateUser($email_or_username, $password) { // Validates (authenticates) a user.
		$provider = Membership::getProvider();
		return $provider->validateUser($email_or_username, $password);
	}
	public static function getUsersByOnline() { // Gets the number of users online
		$provider = Membership::getProvider();
		return $provider->getUsersByOnline($email_or_username);
	}
	public static function logOut() { // Log user out
		$provider = Membership::getProvider();
		return $provider->logOut();
	}
	public static function getParameter($parameter, $user_id=null) {
		$provider = Membership::getProvider();
		return $provider->getParameter($parameter, $user_id);
	}
	public static function setParameter($parameter, $value, $user_id=null) {
		$provider = Membership::getProvider();
		return $provider->setParameter($parameter, $value, $user_id);
	}
}
