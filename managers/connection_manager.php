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
 * 03/05/2015	(Robert Jones) Changed mysql_* implementation to mysqli_*
 */
class DatabaseSetting {
	private $host, $username, $password, $database;
	function __construct($host, $username, $password, $database) {
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
	private $defaultConnectionName = 'default';
	
	public static function getInstance() {
		$_this = Singleton::getInstance('ConnectionManager');
		return $_this;
	}

	/**
	 * @param string $connection_name
	 * @return null
	 * @throws
	 */
	public static function getConnection($connection_name=null) {
		$_this = ConnectionManager::getInstance();
		$connection_name = $connection_name ?: $_this->defaultConnectionName;
		
		$config = ConfigurationManager::getConfig();
		$config_connection = (isset($config['database']['connections'][$connection_name])) ? $config['database']['connections'][$connection_name] : null;

		if (!isset($_this->connections[$connection_name]) && null !== $config_connection) {

			$host = (isset($config_connection['host'])) ? $config_connection['host'] : '';
			$username = (isset($config_connection['username'])) ? $config_connection['username'] : '';
			$password = (isset($config_connection['password'])) ? $config_connection['password'] : '';
			$database = (isset($config_connection['database'])) ? $config_connection['database'] : '';

			$_this->connections[$connection_name] = mysqli_connect(
				$host,
				$username,
				$password,
				$database
			);

		}

		// Make sure connection exists
		if (!isset($_this->connections[$connection_name])) throw new MissingConnectionException(sprintf('Unable to locate database connection %s', $connection_name));

		return $_this->connections[$connection_name];
	}

	public static function hasConnection($connection_name=null) {
		try {
			self::getConnection($connection_name);
		} catch (MissingConnectionException $e) {
			return false;
		}

		return true;
	}

	/**
	 * @deprecated
	 */
	public static function addConnection($connection_name, $database_setting) {}
	/**
	 * @deprecated
	 */
	private static function _killConnection($connection_name) {
		$_this = ConnectionManager::getInstance();
		if (isset($_this->connections[$connection_name])) {
			mysqli_close($_this->connections[$connection_name]);
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

			$config = ConfigurationManager::getConfig();
			$table_prefix = (isset($config['database']['tableSettings']['prefix'])) ? $config['database']['tableSettings']['prefix'] : '';

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

			$config = ConfigurationManager::getConfig();
			$config_db_tables = (isset($config['database']['tables'])) ? $config['database']['tables'] : array();

			$use_global_prefix = true;

			if (isset($config_db_tables[$table_key])) {

				if (
					isset($config_db_tables[$table_key]['useGlobalPrefix']) &&
					$config_db_tables[$table_key]['useGlobalPrefix'] === false
				) {
					$use_global_prefix = false;
				}

				if (isset($config_db_tables[$table_key]['key'])) {

					$table_name = $config_db_tables[$table_key]['key'];

				}

			}

			// If table name is not defined then just assume the key is the table name
			if (null === $table_name) $table_name = $table_key;

			// Prepend global prefix
			if ($use_global_prefix) $table_name = $_this->getTablePrefix() . $table_name;

		}

		if (is_null($table_name)) $table_name = $_this->getTablePrefix() . $table_key;
		
		return $table_name;
	}
	
	// TODO: Review whether this can be removed depecrated since it does not appear to be used by getTableKey() below
	// Called once a site has been initialized
	public static function finalizeTableSettings() {

		$_this = DatabaseManager::getInstance();
		
		/**
		 * Table prefix is defined for this site/application in the local config.xml included as part of this method... we should commit it since  DatabaseManager::getTablePrefix() will continue to manually lookup the table prefix via the configuration XML until it is manually overridden with a value,
		 * In this case we are asking the DatabaseManager to retrieve the value from the XML via getTablePrefix, and then explicitly setting the prefix via setTablePrefix.  
		 **/
		$table_prefix = $_this->getTablePrefix();
		
		$_this->setTablePrefix($table_prefix);

		$config = ConfigurationManager::getConfig();
		$tables = (isset($config['database']['tables'])) ? $config['database']['tables'] : array();

		foreach($tables as $table_key => $info) {

			$use_global_prefix = isset($info['useGlobalPrefix']) ? $info['useGlobalPrefix'] : true;

			if (isset($info['key'])) {
				self::setTableKey($table_key, $info['key'], $use_global_prefix);
			}

		}

		$_this->m_tablesFinalized = true;
		
	}
	public static function getTableKey($table_name) {

		$_this = DatabaseManager::getInstance();

		$config = ConfigurationManager::getConfig();

		if (isset($config['database']['tables'][$table_name]['key'])) {
			return $config['database']['tables'][$table_name]['key'];
		} else {
			$prefix = $_this->getTablePrefix();
			if (!empty($prefix)) {
				if (substr($table_name, 0, strlen($prefix)) == $prefix) { // Make sure the requested table name has the same prefix as the global prefix
					$check_table_name = substr($table_name, strlen($prefix));

					if (isset($config['database']['tables'][$check_table_name]['key'])) {
						return $config['database']['tables'][$check_table_name]['key'];
					}
				}
			}
		}
		return false;
	}
	
	private static function setTableKey($table_key, $alt_table_key, $use_global_prefix=true) {
		$_this = DatabaseManager::getInstance();
		$_this->tableKeys[$table_key] = new DatabaseTableKey($alt_table_key, $use_global_prefix);
	}
	
}