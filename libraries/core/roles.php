<?php

class Roles {
	// Common to all
	var $m_defaultProvider;
	var $m_providers = array();
	//var $m_permissions = array();
	
	// Common methods
	public static function addPermissionSet($permission, $create, $read, $update, $delete) {
		$provider = Roles::getProvider();
		$provider->addPermissionSet($permission, $create, $read, $update, $delete);
	}
	
	public static function getInstance() { return Singleton::getInstance('Roles'); }
	public static function init() {}
	public static function addProvider($config) {
		$_this = Roles::getInstance();
		$name = $config->get('name');
		$class_name = $config->get('className');
		$provider = new $class_name;
		$provider->config = $config;
		$_this->m_providers[$name] = $provider;
		
		$provider->config = $config;
		$_this->m_providers[$name] = $provider;
	}
	
	public static function setDefaultProvider($provider) {
		$_this = Roles::getInstance();
		$_this->m_defaultProvider = $provider;
	}
	public static function getDefaultProvider() {
		$_this = Roles::getInstance();
		return $_this->m_defaultProvider;
	}
	public static function getProvider($provider_name=null) {
		$_this = Roles::getInstance();
		if (is_null($provider_name)) $provider_name = Roles::getDefaultProvider();
		if (!isset($_this->m_providers[$provider_name])) return false;
		return $_this->m_providers[$provider_name];
	}
	
	public static function getProviders() {
		$_this = Roles::getInstance();
		return $_this->m_providers;
	}
	public static function isStarted() {
	}
	
	// Role specific methods
	/*
	public static function createUser($membership_user_obj=null) { // Creates a new user.
		$provider = Roles::getProvider();
		return $provider->createUser();
	}*/
	public static function addUserToRole($role=null, $user=null, $is_primary=false) {
		$provider = Roles::getProvider();
		return $provider->addUserToRole($role, $user, $is_primary);
	}
	public static function createRole() {
		$provider = Roles::getProvider();
		return $provider->createRole();
	}
	public static function deleteRole() {
		$provider = Roles::getProvider();
		return $provider->deleteRole();
	}
	public static function findUsersInRole() {
		$provider = Roles::getProvider();
		return $provider->findUsersInRole();
	}
	public static function getAllRoles() {
		$provider = Roles::getProvider();
		return $provider->getAllRoles();
	}
	public static function getRolesForUser($membership_user=null) {
		$provider = Roles::getProvider();
		return $provider->getRolesForUser($membership_user);
	}
	public static function getUsersInRole($role_name) {
		$provider = Roles::getProvider();
		return $provider->getUsersInRole($role_name);
	}
	public static function getUsersInRoles(array $role_names, $current_page=null, $results_per_page=null) {
		$provider = Roles::getProvider();
		return $provider->getUsersInRoles($role_names, $current_page, $results_per_page);
	}
	public static function isUserInRole($role_name, $user_id=null) {
		$provider = Roles::getProvider();
		return $provider->isUserInRole($role_name, $user_id);
	}
	public static function removeUserFromRole($role_name, $membership_user=null) {
		$provider = Roles::getProvider();
		return $provider->removeUserFromRole($role_name, $membership_user);
	}
	public static function roleExists() {
		$provider = Roles::getProvider();
		return $provider->roleExists();
	}
	
	public static function canCreate($permission) {
		$provider = Roles::getProvider();
		return $provider->canCreate($permission);
	}
	public static function canRead($permission) {
		$provider = Roles::getProvider();
		return $provider->canRead($permission);
	}
	public static function canUpdate($permission) {
		$provider = Roles::getProvider();
		return $provider->canUpdate($permission);
	}
	public static function canDelete($permission) {
		$provider = Roles::getProvider();
		return $provider->canDelete($permission);
	}
	public static function getStartPage($membership_user=null) {
		$provider = Roles::getProvider();
		return $provider->getStartPage($membership_user);
	}
}