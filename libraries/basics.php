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
		case 'profileprovider': // @deprecated
		case 'profiles': // @deprecated
		case 'providerconfiguration': // @deprecated
		case 'providerbase': // @deprecated
		case 'providercollection': // @deprecated
		case 'providerdictionary': // @deprecated
		case 'roleprovider':
		case 'roles':
		case 'singleton':
		case 'sqlloggingprovider':
		case 'ixmlobject':
		case 'ixmlcreatableobject':
			$class_file = dirname(__FILE__) . '/core/' . $class_lower . '.php';
			include($class_file);
			return;

		case 'missingconnectionexception':
			$class_file = dirname(__FILE__) . '/db/' . $class_lower . '.php';
			include($class_file);
			return;
	}

	// Check library paths
	$paths = PathManager::getPaths();
	
	// Original core framework classes for Logic, DataAccessObject (DAO), and Struct data objects
	$has_namespace = (strpos($class_lower, '/') !== false);
	$is_orig_logic = !$has_namespace && (substr($class_lower, -5) == 'logic');
	$is_orig_dao = !$has_namespace && (substr($class_lower, -3) == 'dao');
	$is_orig_struct = !$has_namespace && (substr($class_lower, -6) == 'struct');
	
	$class_path = null;
	
	foreach($paths as $base_path) {
		
		$class_path = $base_path . 'lib' . DIRECTORY_SEPARATOR .  str_replace('\\', DIRECTORY_SEPARATOR, $class_name) . '.php';
		
		// Override class_path for core logic, dao, struct classes
		if ($is_orig_logic || $is_orig_dao || $is_orig_struct) {
			$core_dir = '';
			$class_dir = '';
			$append_file = '';
			if ($is_orig_logic) {
				$core_dir = 'logic';
				$class_dir = substr($class_lower, 0, -5); // remove "logic" for dir/file names
			} else if ($is_orig_dao) {
				$core_dir = 'data';
				$class_dir = substr($class_lower, 0, -3); // remove "dao" for dir/file names
				$append_file = '_dao';
			} else if ($is_orig_struct) {
				$core_dir = 'data';
				$class_dir = substr($class_lower, 0, -6); // remove "struct" for dir/file names
				$append_file = '_structure';
			}
			
			$class_path = $base_path . $core_dir . DIRECTORY_SEPARATOR . $class_dir . DIRECTORY_SEPARATOR . $class_dir . $append_file . '.php';
		}
		
		if (file_exists($class_path)) {
			require_once($class_path);
			return;
		}
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