<?php
/**
 * Change Log
 * 04/01/2009	[Robert Jones] Modified addConnection()
 *		Changed addConnection so that it resets $this->connection[$connection_name] in the event that a connection has already been established under that $connection_name
 * 		The change was required because the FrameworkManager first creates a database connection to retrieve information about a site to make sure it is valid.  It then loads
 *		the site configuration file, which generally overwrites one or more (default) connection settings.  If we do not reset the $connection_name within $this->connections,
 *		then the old connection is used, which creates a problem when the actual site is stored in a different database than the core "sites" table.
 * 02/01/2010	(Robert Jones) Added DatabaseManager::isTableDefined($table_name) to check if a table name has been defined by the configuration
 * 02/01/2010	(Robert Jones) Added DatabaseManager::getTablePrefix()
 * 05/11/2010	(Robert Jones) Removed setTablePrefix and changed getTablePrefix to pull the value from  ConfigurationManager::getConfig()
 * 05/14/2010	(Robert Jones) Added getTableKey() to lookup a table's key by its physical table name
 * 09/20/2011	(Robert Jones) Readded setTableKey() to allow programmatically overriding table prefix
 * 09/09/2012	(Robert Jones) Changed private method setTable to setTableKey
 */
class DatabaseSetting {
	private $host, $username, $password, $database;
	function DatabaseSetting($host, $username, $password, $database) {
		$this->host = $host;
		$this->username = $username;
		$this->password = $password;
		$this->database = $database;
	}
	public function getHost() { return $this->host; }
	public function getUsername() { return $this->username; }
	public function getPassword() { return $this->password; }
	public function getDatabase() { return $this->database; }
	
}
class ConnectionManager {
	var $databases = array();
	var $connections = array();
	var $connection_configs = array();
	
	public static function getInstance() {
		$_this = Singleton::getInstance('ConnectionManager');
		return $_this;
	}
	
	public static function getConnectionSettings($connection_name='default') {
		$_this = ConnectionManager::getInstance();

		if (isset($_this->connection_configs[$connection_name])) {
			return $_this->connection_configs[$connection_name];
		}
		
		return false;
	}
	
	public static function getConnection($connection_name='default') {
		
		$_this = ConnectionManager::getInstance();

		if (!isset($_this->connections[$connection_name]) && ($settings = $_this->getConnectionSettings($connection_name))) {

			$_this->connections[$connection_name] = mysql_connect(
				$settings->getHost(),
				$settings->getUsername(),
				$settings->getPassword());
			$_this->databases[$connection_name] = mysql_select_db($settings->getDatabase(), $_this->connections[$connection_name]);
		}

		#return $_this->databases[$connection_name];
		return $_this->connections[$connection_name];
	}
	public static function addConnection($connection_name, $database_setting) {
		$_this = ConnectionManager::getInstance();
		
		/**
		 * If a connection already exists under this $connection_name, then it needs to be killed/reset
		 */
		ConnectionManager::_killConnection($connection_name);
		
		$_this->connection_configs[$connection_name] = $database_setting;
		return true;
	}
	
	private static function _killConnection($connection_name) {
		$_this = ConnectionManager::getInstance();
		if (isset($_this->connections[$connection_name])) {
			mysql_close($_this->connections[$connection_name]);
			unset($_this->connections[$connection_name]);
		}
	}
}
/**
 * A quick definition for table information used by DatabaseManager
 **/
class DatabaseTableKey {
	/**
	 * Constructor
	 * @param string $altTableKey The table key to be used in place of a defined table key
	 * @param $useGlobalPrefix Whether to use the global table prefix when using DatabaseManager::getTable()
	 **/
	private $altTableKey;
	private $useGlobalPrefix;
	function __construct($alt_table_key, $use_global_prefix=false) {
		$this->altTableKey = $alt_table_key;
		$this->useGlobalPrefix = $use_global_prefix;
	}
	public function getAltTableKey() { return $this->altTableKey; }
	public function useGlobalPrefix() { return $this->useGlobalPrefix; }
}

class DatabaseManager {
	#var $m_tables = array();
	/**
	 * Maps table keys to alternate names
	 **/
	private $tableKeys = array();
	var $m_tablePrefix;
	var $m_tablesFinalized = false;
	
	public static function getInstance() {
		return Singleton::getInstance('DatabaseManager');
	}
	/**
	 * Gets the global table prefix to be used on all tables.  Uses lazy loading so that information is only loaded once it is requested.
	 */
	public static function getTablePrefix() {
		static $total_time = 0;
		
		$_this = DatabaseManager::getInstance();
		
		/**
		 * Lookup table prefix if it has not already been set
		 **/
		if (is_null($_this->m_tablePrefix)) {

			$table_prefix = '';
			$xml_config = ConfigurationManager::getConfig();
			if ($xml_table_sections = $xml_config->getPath('database/tables')) {
				foreach($xml_table_sections as $xml_table_section) {
					if ($prefix = $xml_table_section->getParam('prefix')) {
						$table_prefix = $prefix;
					}
				}
			}
			
			return $table_prefix;
		} else {
			return $_this->m_tablePrefix;
		}
	}
	
	public static function setTablePrefix($prefix) {
		$_this = DatabaseManager::getInstance();
		$_this->m_tablePrefix = $prefix;
	}

	public static function isTableDefined($table_key) {
		$_this = DatabaseManager::getInstance();
		return isset($_this->m_tables[$table_key]);
	}
	
	public static function getTable($table_key) {
		static $total_time = 0;
		
		$_this = DatabaseManager::getInstance();
		
		$table_name = null;
		
		$cached = false;
		
		if ($_this->m_tablesFinalized) {
			
			$cached = true;
			
			#if (isset($_this->m_tables[$table_key])) $table_name = $_this->m_tables[$table_key];
			/**
			 * Translate table key to alternate name if defined
			 **/
			if (isset($_this->tableKeys[$table_key])) {
				
				$database_table_key = $_this->tableKeys[$table_key];
				
				$table_key = $database_table_key->getAltTableKey();
				$use_global_prefix = $database_table_key->useGlobalPrefix();
				
			}
			
		} else {
			$xml_config = ConfigurationManager::getConfig();
			
			if ($xml_tables = $xml_config->getPath('database/tables/add[@name="' . $table_key . '"]')) {
				$use_global_prefix = true;
				foreach($xml_tables as $xml_table) {
					if ($xml_table->getParam('useTablePrefix')) {
						 $use_global_prefix = !($table->getParam('useGlobalPrefix')=='false');
					}
					if ($table_name = $xml_table->getParam('value')) {
						if ($use_global_prefix) {
							$table_name = $_this->getTablePrefix() . $table_name;
						}
					}
				}
			}
		}
		/*
		if (is_null($table_name)) return $table_key;
		else return $table_name;
		*/

		if (is_null($table_name)) $table_name = $_this->getTablePrefix() . $table_key;
		
		return $table_name;
	}
	
	// Called once a site has been initialized
	public static function finalizeTableSettings() {
		
		$_this = DatabaseManager::getInstance();
		
		/**
		 * Table prefix is defined for this site/application in the local config.xml included as part of this method... we should commit it since  DatabaseManager::getTablePrefix() will continue to manually lookup the table prefix via the configuration XML until it is manually overridden with a value,
		 * In this case we are asking the DatabaseManager to retrieve the value from the XML via getTablePrefix, and then explicitly setting the prefix via setTablePrefix.  
		 **/
		$table_prefix = $_this->getTablePrefix();
		
		$_this->setTablePrefix($table_prefix);
		
		$xml_config = ConfigurationManager::getConfig();
			
		if ($xml_tables = $xml_config->getPath('database/tables/add')) {
			
			foreach($xml_tables as $xml_table) {
				
				$use_global_prefix = true;
				
				if ($xml_table->getParam('useTablePrefix')) {
					 $use_global_prefix = !($xml_table->getParam('useGlobalPrefix')=='false');
				}
				
				if ($alt_table_key = $xml_table->getParam('value')) {
					
					#if ($use_global_prefix) {
					#	$alt_table_key = $_this->getTablePrefix() . $table_name;
					#}
					
					self::setTableKey($xml_table->getParam('name'), $alt_table_key, $use_global_prefix);
				}
			}
			
		}
		
		$_this->m_tablesFinalized = true;
		
	}
	public static function getTableKey($table_name) {
		$_this = DatabaseManager::getInstance();
		$xml_config = ConfigurationManager::getConfig();
		// Check the obviouos first
		if ($xml_table = $xml_config->getPathSingleLast('database/tables/add[@value="' . $table_name . '"]')) {
			return $xml_table->getParam('name');
		// Then check if a global prefix exists and remove that from the search
		} else {
			$prefix = $_this->getTablePrefix();
			if (!empty($prefix)) {
				if (substr($table_name, 0, strlen($prefix)) == $prefix) { // Make sure the requested table name has the same prefix as the global prefix
					$check_table_name = substr($table_name, strlen($prefix));
					
					if ($xml_table = $xml_config->getPathSingleLast('database/tables/add[@value="' . $check_table_name . '"]')) {
						return $xml_table->getParam('name');
					}
				}
			}
		}
		return false;
	}
	
	private static function setTableKey($table_key, $alt_table_key, $use_global_prefix=true) {
		$_this = DatabaseManager::getInstance();
		$_this->tableKeys[$table_key] = new DatabaseTableKey($alt_table_key, $use_global_prefix);
		/*
		$_this->m_tables[$table_key] = array(
			'table_name' => $table_name,
			'use_global_prefix' => $use_global_prefix
		);
		*/
	}
	
}

?>