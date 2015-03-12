<?php

/**
 * Custodian record severity
 * RFC 3164
 * 0	Emergency: system is unusable
 * 1	Alert: action must be taken immediately
 * 2	Critical: critical conditions
 * 3	Error: error conditions
 * 4	Warning: warning conditions
 * 5	Notice: normal but significant condition
 * 6	Informational: informational messages
 * 7	Debug: debug-level messages
 **/
define('MESSAGE_TYPE_NOTIFICATION',	'Notification'); // User notifications
define('MESSAGE_TYPE_ALERT',		'AdminAlert'); // Admin-only notifications
define('MESSAGE_TYPE_ERROR',		'UserError'); // User error messages
define('MESSAGE_TYPE_DEBUG',		'Debug'); // Debug message - for admin only
define('MESSAGE_TYPE_DEBUG_DB',		'DebugDatabase');
define('MESSAGE_TYPE_LOG',		'LogEvent'); // Not yet used, but consider using 

define('CUSTODIAN_EMERGENCY',		0); // Emergency: system is unusable
define('CUSTODIAN_ALERT',		1); // Alert: action must be taken immediately
define('CUSTODIAN_CRITICAL',		2); // Critical: critical conditions
define('CUSTODIAN_ERROR',		3); // Error: error conditions
define('CUSTODIAN_WARNING',		4); // Warning: warning conditions
define('CUSTODIAN_NOTICE',		5); // Notice: normal but significant condition
define('CUSTODIAN_INFORMATIONAL',	6); // Informational: informational messages
define('CUSTODIAN_DEBUG',		7); // Debug: debug-level messages
 
FrameworkManager::loadLogic('custodian');
FrameworkManager::loadStruct('custodian');

class Custodian {

	/**
	 * Log anything
	 * 
	 * @param string $type A string that helps identify the source of the error (e.g. PHP, MyClassName, etc.)
	 * @param string $message The message to be logged.  Dynamic values should be input as ${var_name} with their values defined in $variables
	 * @param Dictionary $variables A dictionary of values defined in $message
	 * @param int $severity one of CUSTODIAN_* constants
	 * ###### REMOVE: @param string $link A link that might be helpful in resolving the issue, such as a link directly to the page where the error occurred
	 * @param string $location A file location where the note occured; possibly in the format [file_name]:[line_number]
	 **/
	public static function log($type, $message, Dictionary $variables=null, $severity=CUSTODIAN_NOTICE, $location=null) {
		static $is_logging = false; // Make sure we do not cause an infinite loop if PHP errors are being captured via set_error_handler and set_exception_handler

		if (!is_a($variables, 'ConfigDictionary')) $variables = new ConfigDictionary($variables);// Make sure this is a dictionary object that can be stored in the database
		if (is_null($variables)) $variables = new ConfigDictionary();
		if (is_null($severity)) $severity = CUSTODIAN_NOTICE;
		
		if (!$is_logging && FrameworkManager::isSiteInitialized()) {
			$is_logging = true;
			
			$struct = new CustodianStruct();
			
			$struct->hostname	= isset($_SERVER['HTTP_HOST']) ? $_SERVER['HTTP_HOST'] : '';
			#$struct->link		= $link;
			$struct->location	= $location;
			$struct->message 	= $message;
			$struct->remote_ip	= isset($_SERVER['REMOTE_ADDR']) ? $_SERVER['REMOTE_ADDR'] : '0.0.0.0';
			$struct->referrer	= isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : '';
			$struct->severity	= $severity;
			$struct->type		= $type;
			$struct->variables	= $variables->toString();
			
			/*
			echo '<div style="border:1px solid #000;padding:5px;margin:10px;">';
			echo '<pre>';
			print_r($struct);
			echo '</pre>';
			echo '</div>';
			*/
			CustodianLogic::save($struct);
						
			$is_logging = false;
		}
		
	}
	
	private static function getSeverityLookup() {
		return array(
			0 => array('name' => 'Emergency',	'description' => 'System is unusable'),
			1 => array('name' => 'Alert', 		'description' => 'Action must be taken immediately'),
			2 => array('name' => 'Critical',	'description' => 'Critical conditions'),
			3 => array('name' => 'Error',		'description' => 'Error conditions'),
			4 => array('name' => 'Warning',		'description' => 'Warning conditions'),
			5 => array('name' => 'Notice',		'description' => 'Normal but significant condition'),
			6 => array('name' => 'Informational',	'description' => 'Informational messages'),
			7 => array('name' => 'Debug',		'description' => 'Debug-level messages')
		);
	}
	public static function getSeverityName($id) {
		$s = Custodian::getSeverityLookup($id);
		return (isset($s[$id]) ? $s[$id]['name'] : 'Unknown');
	}
	public static function getSeverityDescription($id) {
		$s = Custodian::getSeverityLookup($id);
		return (isset($s[$id]) ? $s[$id]['description'] : 'Unknown');
	}
	
	public static function enableCaptureErrors() {
		/**
		 * Register error handlers only once a site has been initialized
		 **/
		set_error_handler(array('Custodian', 'handlePhpError'));
		set_exception_handler(array('Custodian', 'handlePhpException'));
		register_shutdown_function(array('Custodian', 'handleShutdown'));
	}
	
	public static function disableCaptureExceptions() {
		restore_error_handler();
		restore_exception_handler();
	}
	
	public static function handlePhpError($e_no, $e_msg, $e_file, $e_line, $e_context=null) {
		$severity = Custodian::getSeverityFromPhpError($e_no);
		$d = new Dictionary(array('e_no'=>$e_no,'e_msg'=>$e_msg));
		Custodian::log('php', 'PHP Error: ${e_no}.  ${e_msg}', $d, $severity, $e_file.':'.$e_line);
	}
	
	public static function handlePhpException($exception) {
		$d = new Dictionary(array('e_msg'=> $exception->getMessage()));
		Custodian::log('php', 'PHP Exception: ${e_msg}', $d, CUSTODIAN_CRITICAL, $exception->getFile(), $exception->getFile().':'.$exception->getLine());
	}
	public static function handleShutdown() {
		
		$error = error_get_last();
		
		// Capture any errors that might have occurred
		if (!is_null($error)) {
			$e_no	= isset($error['type']) ? $error['type'] : 0;
			$e_msg	= isset($error['message']) ? $error['message'] : '';
			$e_line	= isset($error['line']) ? $error['line'] : 0;
			$e_file	= isset($error['file']) ? $error['file'] : '';
			
			$error_level = self::getSeverityFromPhpError($e_no);
			
			// Capture errors that are an emergency since they probably did not get captured
			if ($error_level == CUSTODIAN_EMERGENCY) {
					
				Custodian::handlePhpError($e_no, $e_msg, $e_file, $e_line);
				
			}
		}
	}
	
	private static function getSeverityFromPhpError($e_no) {
		$map = array(
			E_ERROR			=> CUSTODIAN_EMERGENCY, /* E_ERROR(1) - Fatal run-time errors. These indicate errors that can not be recovered from, such as a memory allocation problem. Execution of the script is halted. */
			E_WARNING		=> CUSTODIAN_WARNING, /* E_WARNING(2) - Run-time warnings (non-fatal errors). Execution of the script is not halted. */
			E_PARSE			=> CUSTODIAN_WARNING, /* E_PARSE(4) - Compile-time parse errors. Parse errors should only be generated by the parser. */
			E_NOTICE		=> CUSTODIAN_NOTICE, /* E_NOTICE(8) - Run-time notices. Indicate that the script encountered something that could indicate an error, but could also happen in the normal course of running a script. */
			E_CORE_ERROR		=> CUSTODIAN_ERROR, /* E_CORE_ERROR(16) - Fatal errors that occur during PHP's initial startup. This is like an E_ERROR, except it is generated by the core of PHP. */
			E_CORE_WARNING		=> CUSTODIAN_WARNING, /* E_CORE_WARNING(32) - Warnings (non-fatal errors) that occur during PHP's initial startup. This is like an E_WARNING, except it is generated by the core of PHP. */
			E_COMPILE_ERROR		=> CUSTODIAN_ERROR, /* E_COMPILE_ERROR(64) - Fatal compile-time errors. This is like an E_ERROR, except it is generated by the Zend Scripting Engine. */
			E_COMPILE_WARNING	=> CUSTODIAN_WARNING, /* E_COMPILE_WARNING(128) - Compile-time warnings (non-fatal errors). This is like an E_WARNING, except it is generated by the Zend Scripting Engine. */
			E_USER_ERROR		=> CUSTODIAN_ERROR, /* E_USER_ERROR(256) - User-generated error message. This is like an E_ERROR, except it is generated in PHP code by using the PHP function trigger_error(). */
			E_USER_WARNING		=> CUSTODIAN_WARNING, /* E_USER_WARNING(512) - User-generated warning message. This is like an E_WARNING, except it is generated in PHP code by using the PHP function trigger_error(). */
			E_USER_NOTICE		=> CUSTODIAN_NOTICE, /* E_USER_NOTICE(1024) - User-generated notice message. This is like an E_NOTICE, except it is generated in PHP code by using the PHP function trigger_error(). */
			E_STRICT		=> CUSTODIAN_NOTICE, /* E_STRICT(2048) - Enable to have PHP suggest changes to your code which will ensure the best interoperability and forward compatibility of your code. */
			E_RECOVERABLE_ERROR	=> CUSTODIAN_WARNING, /* E_RECOVERABLE_ERROR(4096) - Catchable fatal error. It indicates that a probably dangerous error occured, but did not leave the Engine in an unstable state. If the error is not caught by a user defined handle (see also set_error_handler()), the application aborts as it was an E_ERROR. */
			E_DEPRECATED		=> CUSTODIAN_INFORMATIONAL, /* E_DEPRECATED(8192) - Run-time notices. Enable this to receive warnings about code that will not work in future versions. */
			E_USER_DEPRECATED	=> CUSTODIAN_ERROR, /* E_USER_DEPRECATED(16384) - User-generated warning message. This is like an E_DEPRECATED, except it is generated in PHP code by using the PHP function trigger_error(). */
		);
		if (isset($map[$e_no])) return $map[$e_no];
		else return CUSTODIAN_NOTICE;
	}
}

/**
 * Singleton class for handling errors, debug, and alerts
 **/

class MessageManager {
	var $m_messageStack = array();
	public static function init() { return true; }

	public static function _initMessageType($message_type) {
		$_this = Singleton::getInstance('MessageManager');
		if (!isset($_this->m_messageStack[$message_type])) $_this->m_messageStack[$message_type] = new Collection();
		return $_this;
	}
	
	public static function addMessage($message_type, $message) {
		MessageManager::_initMessageType($message_type);
		$_this = Singleton::getInstance('MessageManager');
		$_this->m_messageStack[$message_type]->add($message);
	}
	public static function getMessageCountByType($message_type) {
		MessageManager::_initMessageType($message_type);
		$_this = Singleton::getInstance('MessageManager');
		
		return $_this->m_messageStack[$message_type]->getCount();
	}
	public static function getMessagesByType($message_type) {
		MessageManager::_initMessageType($message_type);
		$_this = Singleton::getInstance('MessageManager');
		return $_this->m_messageStack[$message_type];
	}
	public static function anyMessagesByType($message_type) {
		return (MessageManager::getMessageCountByType($message_type) > 0);
	}
}
// Singleton error manager - uses MessageManager
class ErrorManager {
	public static function addAlert($alert_message) {
		MessageManager::addMessage(MESSAGE_TYPE_ALERT, $alert_message);
	}
	
	public static function addError($error_message, $alert_message=null) {
		if (is_array($error_message)) {
			foreach($error_message as $error) {
				MessageManager::addMessage(MESSAGE_TYPE_ERROR, $error);
			}
		} else {
			MessageManager::addMessage(MESSAGE_TYPE_ERROR, $error_message);
		}
		if (!is_null($alert_message)) ErrorManager::addAlert($alert_message);
	}
	public static function getDisplayErrors() {
		return MessageManager::getMessagesByType(MESSAGE_TYPE_ERROR);
	}
	public static function anyDisplayErrors() { // User errors only
		return MessageManager::anyMessagesByType(MESSAGE_TYPE_ERROR);
	}
}
class DebugDBManager {
	var $m_totalTime = 0;
	public static function addMessage($message) {
		MessageManager::addMessage(MESSAGE_TYPE_DEBUG_DB, $message);
	}
	public static function getMessages() { return MessageManager::getMessagesByType(MESSAGE_TYPE_DEBUG_DB); }
	public static function addTime($time) {
		$_this = Singleton::getInstance('DebugDBManager');
		$_this->m_totalTime += $time;
	}
	public static function getTotalTime() { 
		$_this = Singleton::getInstance('DebugDBManager');
		return $_this->m_totalTime;
	}
}
class DebugManager {
	public static function addMessage($message) {
		MessageManager::addMessage(MESSAGE_TYPE_DEBUG, $message);
	}
	public static function getMessages() { return MessageManager::getMessagesByType(MESSAGE_TYPE_DEBUG); }
}
class NotificationManager {
	public static function addMessage($message) {
		MessageManager::addMessage(MESSAGE_TYPE_NOTIFICATION, $message);
	}
	public static function getMessages() { return MessageManager::getMessagesByType(MESSAGE_TYPE_NOTIFICATION); }
	public static function anyNotifications() { // User errors only
		return MessageManager::anyMessagesByType(MESSAGE_TYPE_NOTIFICATION);
	}
}
?>