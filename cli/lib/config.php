<?php

if (!defined('DIR_LOCAL_FRAMEWORK'))		define('DIR_LOCAL_FRAMEWORK', 		dirname(dirname(dirname(dirname(__FILE__)))) . '/');
if (!defined('DIR_LOCAL_FRAMEWORK_BASE'))		define('DIR_LOCAL_FRAMEWORK_BASE', 	DIR_LOCAL_FRAMEWORK . 'base/');
#if (!defined('DIR_LOCAL_FRAMEWORK_SITES'))	define('DIR_LOCAL_FRAMEWORK_SITES', 	DIR_LOCAL_FRAMEWORK . 'sites/');
if (!defined('FILE_FRAMEWORK'))			define('FILE_FRAMEWORK',		DIR_LOCAL_FRAMEWORK_BASE . 'managers/framework_manager.php');

if (!defined('DIR_FS_CLI'))			define('DIR_FS_CLI', 			DIR_LOCAL_FRAMEWORK_BASE . 'cli/');
if (!defined('DIR_FS_CLI_LIB'))			define('DIR_FS_CLI_LIB', 		DIR_FS_CLI . 'lib/');

?>