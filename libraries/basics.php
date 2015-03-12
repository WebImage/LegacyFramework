<?php
/**
 * Revision History
 * 04/09/2009	(Robert Jones) changed getNext() to reset the index after it has reached the last entry
 * 02/01/2010	(Robert Jones) added ProviderDictionary class which will need to replace ProviderCollection
 * 05/11/2010	(Robert Jones) Modified Membership::addProvider() to lazily add providers without initializing
 */
/**
 * Allows an array to be accessed as if it were a collection/iteration of objects
 */
/**
 * Core classes that were part of the framework when it was first built have all been moved to libraries/core/[class_name].php
 **/
function handle_autoload_core_classes($class_name) {

	$class_lower = strtolower($class_name);
	
	switch ($class_lower) {
		case 'abstractrequesthandler':
		case 'collection':
		case 'configdictionary':
		case 'dictionaryfield':
		case 'dictionaryfieldcollection':
		case 'dictionary':
		case 'dictionaryhierarchy':
		case 'icollection':
		case 'imembership':
		case 'irequesthandler':
		case 'lazyproviderdictionary':
		case 'lazyprovider':
		case 'logging':
		case 'loggingprovider':
		case 'loggingprovidercollection':
		case 'membership':
		case 'membershipprovider':
		case 'membershipprovidercollection':
		case 'membershipproviderconfiguration':
		case 'membershipuser':
		case 'permission':
		case 'profileprovider':
		case 'profiles':
		case 'providerconfiguration':
		case 'providerbase':
		case 'providercollection':
		case 'providerdictionary':
		case 'providermanager':
		case 'roleprovider':
		case 'roles':
		case 'singleton':
		case 'sqlloggingprovider':
		case 'ixmlobject':
		case 'ixmlcreatableobject':
			$class_file = dirname(__FILE__) . '/core/' . $class_lower . '.php';
			include($class_file);
			break;

		case 'missingconnectionexception':
			$class_file = dirname(__FILE__) . '/db/' . $class_lower . '.php';
			include($class_file);
			break;
	}

	if (substr($class_name, 0, 8) == 'WebImage') {
		$path = dirname(dirname(__FILE__)) . DIRECTORY_SEPARATOR . 'lib' . DIRECTORY_SEPARATOR . str_replace('\\', DIRECTORY_SEPARATOR, $class_name) . '.php';

		if (file_exists($path)) require_once($path);
	}

}
spl_autoload_register('handle_autoload_core_classes');

if (!function_exists('property_exists')) {
	function property_exists($class, $property) {
		$class_name = get_class($class);
		$vars = get_class_vars($class_name);
		return array_key_exists($property, $vars);
	}
}

function str_to_seo($url_to_use) {
	$url_to_use = trim(str_replace("the", "", $url_to_use));
	$url_to_use = strtolower( str_replace("_", "-", $url_to_use));
	//$url_to_use = preg_replace("/[^0-9a-z -]/", "", $url_to_use);
	$url_to_use = preg_replace("/[^0-9a-z ]/", "", $url_to_use);
	$url_to_use = str_replace('  ', ' ', $url_to_use); // Replace extra spaces
	$url_to_use = str_replace(" ", "-", $url_to_use);
	return $url_to_use;
}

function calculate_age($birthday_timestamp, $on_date=null) {
	if (is_null($on_date)) $on_date = mktime(0, 0, 0, date('m'), date('d'), date('Y')); // Make on_date today if not specified
	$age = date('Y', $on_date) - date('Y', $birthday_timestamp);
	$months = date('m', $on_date) - date('m', $birthday_timestamp);
	$days = date('d', $on_date) - date('d', $birthday_timestamp);
	
	/**
	 * Check if it is not yet the user's birthday for the given year and substract age by 1 if not
	 */
	if ($months < 0) $age--;
	else if ($months == 0 && $days < 0) $age--;
	
	return $age;
}