<?php

class MembershipProvider extends ProviderBase {
	function createUser($membership_struct) {}
	function createUserAndLogin($membership_struct) {}
	function deleteUser($user) {} // Deletes a user.
	function updateUser($user) {} // Updates a user with new information.
	function getUser($user_id=null) {} // Gets logged-in user if $user_id IS NULL, otherwise returns user defined by user_id
	function getUsers($email_or_username) {} // Returns a list of users.  OR Searches for users by username or e-mail address.
	function findUserByName($name) {} // Finds a user by name or e-mail.
	function findUserByEmail($email) {}
	function validateUser($username, $password) {} // Validates (authenticates) a user.
	function loginAs($user_id) {}
	function getUsersByOnline() {} // Gets the number of users online
	function getParameter($parameter, $user_id=null) { return false; } // Get User Parameter
	function setParameter($parameter, $value, $user_id=null) { return false; } // Set User Parameter
}