<?php

class IMembership {
	var $m_loginUrl;
	var $m_requireSSL;
	var $m_slidingExpiration;
	function createUser($params) {} // Creates a new user.
	function createUserAndLogin($params) {}
	function deleteUser($user) {} // Deletes a user.
	function updateUser($user) {} // Updates a user with new information.
	function getUsers($email_or_username) {} // Returns a list of users.  OR Searches for users by username or e-mail address.
	function findUserByName($name) {} // Finds a user by name or e-mail.
	function findUserByEmail($email) {}
	function validateUser($username, $password) {} // Validates (authenticates) a user.
	function getUsersByOnline() {} // Gets the number of users online.
}
