<?php
/*
DAOSearch()
	addSearchField($search_field);
	addOrder($order_by_field);
DAOSearchField($table_key, $field_key, $value=null);
DAOSearchFieldWildcard($table_key, $field_key, $value, $wildcard=false);
DAOSearchFieldRange($table_key, $field_key, $low_value=null, $high_value=null);
06/29/2016	(Robert Jones) Changed ResultSet::getTotalResults() to return getCount() when $totalResults has not been manually specified
01/20/2010	(Robert Jones) Added DAOSearchFieldNull and DAOSearchFieldNotNull
08/03/2010	(Robert Jones) Added extra code to pagination result count to remove "ORDER BY" clauses from the query string
08/03/2010	(Robert Jones) Added extra code to do special calculation on GROUP BY pagination calculations
09/05/2010	(Robert Jones) Moved DAOSearch functionality to db.daosearch library
09/08/2010	(Robert Jones) Added addUpdateField() to add fields
*/

class ResultSet extends Collection {
	var $totalResults; // Stores the total numbers of rows returned for a SELECT query - useful when a LIMIT [start], [number_to_fetch] is used against DataAccessObject::selectQuery() because it allows you to retrieve the number of results for the entire query, as oppposed to the count returned by the query with a LIMIT
	var $currentPage;
	var $resultsPerPage;

	function setTotalResults($total_real_results) { $this->totalResults = $total_real_results; }
	function setCurrentPage($current_page) { $this->currentPage = $current_page; }
	function setResultsPerPage($results_per_page) { $this->resultsPerPage = $results_per_page; }

	function getTotalResults() {
		if (null == $this->totalResults) return $this->getCount();
		return $this->totalResults;
	}
	function getCurrentPage() { return $this->currentPage; }
	function getResultsPerPage() { return $this->resultsPerPage; }
	function getTotalPages() {
		$pages = 0;
		if ($this->getCount() > 0) {
			$total_results = $this->getTotalResults();
			$results_per_page = $this->getResultsPerPage();
			if (!empty($results_per_page)) {
				$pages = ceil($this->getTotalResults() / $this->getResultsPerPage());
			} else if ($total_results > 0) {
				$pages = 1;
			}
		}
		return $pages;
	}

}

class ResultSetHelper {
	public static function buildResultSetFromArray($array, $key_field='id', $text_field='name') {
		$rs = new ResultSet();
		foreach($array as $key=>$text) {
			$row = new stdClass();
			$row->$key_field = $key;
			$row->$text_field = $text;
			$rs->add($row);
		}
		return $rs;
	}
}

/*
# Preparing for future query style:

$query = $this->select()
	->model('test')
	->alias('my_table')
	->columns('created', 'created_by')
	->leftJoin('listing_categories', 'id', 'my_table.listing_category_id', array('jointable-column'))
	->leftJoin('whatever', array('id' => 'my_table.id'))
	->where('id', 10)
	->where('id', '=', 10)
	->where('id', 'between', 10, 30)
	->where('id', '>=', '10')
	->where("id > 10 AND (start_date >= 'test' OR ignore_dates = 1)")
	->whereBetween('id', 5, 10)
	->whereGreaterThan('id', 5, true) // TRUE indicates that the operation should be greater than or equal
	->whereLessThan('id', 5, true)
	->whereLike('name', 'Robert')
	->groupBy('column', 'column2')
	->orderBy('column ASC', 'column DESC')
	->paginate(1, 20)
	->exec();

// How to do or/and groupings in where clause
->orWhere(function($qb) {
	$qb->where('id', 1);
	$qb->where('created', '>=', '2020-04-15 00:00:00');
])

$this->insert()
	->set('first_name', 'Robert')
	->set('last_name', 'Jones')
	->set('membership_id')
	->exec();

$this->delete()
	->where('id', 5)
	->exec();

$this->update()
	->set('last_name', 'Jones II')
	->where('id', 5);
*/
/**
 * Revision History
 *
 * 04/09/2009	Robert: Added setForceInsert() to force INSERT instead of UPDATE in the case that the primary key is not an integer and must be set before saving for the first time
 * 04/09/2009	Robert: Added isNewRecord($struct) to check if the passed struct represents a new record
 * 04/09/2009	Robert: Added tableExists($table_name) to check if a database table exists table exists
 * 02/02/2010	(Robert Jones) Added function anyErrors(), addError($message), and getErrors() to provide error handling
 * 02/02/2010	(Robert Jones) Added function getDatabaseTables() to retrieve a collection of table names
 * 09/08/2010	(Robert Jones) Removed $tablePrefix - not necessary since prefix is handled by DatabaseManager::getTable()
 * 08/17/2011	(Robert Jones) Changed $_forceInsert (removed), setForceInsert(), and isForceInsert() to use setSaveType($save_type) and $_forceSaveType so that UPDATES can also be forced (added constants for SAVE_TYPE_INSERT, SAVE_TYPE_UPDATE
 */
class DataAccessObject {
	const SAVE_TYPE_AUTO = 'save-type-auto';
	const SAVE_TYPE_INSERT = 'save-type-insert';
	const SAVE_TYPE_UPDATE = 'save-type-update';

	var $tableName;
	var $modelName = 'stdClass';
	var $primaryKey = 'id';
	var $updateFields = array(); // Fields to use from passed structure to update in database
	private $separateResultTables = false; // Whether results should be split into separate objects based on the provided table alias
	#var $searchJoins = array();

	/**
	 * Forces INSERT instead of UPDATE when the primaryKey has been defined
	 * This is used where the primaryKey is not a number, i.e. the key is generated by user or logic
	 */
	#var $_forceInsert = false;
	var $_saveType;
	var $_cacheResults = true; // Whether or not to check for cached SQL commands/results

	var $selectQuery = 'SELECT %s FROM %s WHERE %s';
	var $updateQuery = 'UPDATE %s SET %s WHERE %s';
	var $deleteQuery = 'DELETE FROM %s WHERE %s';
	var $insertQuery = 'INSERT INTO %s (%s) VALUES(%s)';

	/**
	 * Pagination options
	 */
	var $_paginate = false;
	var $_currentPage = 1;
	var $_resultsPerPage = 10;

	/**
	 * SQL Errors
	 */
	private $errors = array();

	private $writeConnName = null;
	private $readConnName = null;

	/**
	 * Should be overridden with specific
	 */
	public function load($id) { // Simple query, should be override for more advanced queries
		$conditions = $this->primaryKey . '=\'' . $id . '\'';
		$select_sql = sprintf($this->selectQuery, '*', $this->getTableName(), '`' . $this->primaryKey . "`='". $id . "'");
		$results = $this->selectQuery($select_sql, $this->modelName);
		return $results->getAt(0);
	}

	public function loadAll() {
	/*
		loadAll($extend=array());

		$extend = array(
			'Order'=>'name',
			'StartRow'=>0,
			'DisplayRows'=>5
	*/
		$sql = sprintf($this->selectQuery, '*', $this->getTableName(), '1=1');
		return $this->selectQuery($sql, $this->modelName);
	}
	public function getTableName() { return $this->tableName; }
	public function setTableName($table_name) { $this->tableName = $table_name; }

	public function save($structure_object) {
		if (count($this->updateFields) > 0 && !empty($this->tableName)) {

			$primary_key = $this->primaryKey;
			//$primary_key_value = $structure_object->$primary_key; // Should be able to remove this; derived functionality now in isNewRecord()

			$structure_object_vars = get_object_vars($structure_object);

			// Update this record with who last updated it
			if (array_key_exists('updated', $structure_object_vars)) {
				$structure_object->updated = date('Y-m-d H:i:s');
				if (array_key_exists('updated_by', $structure_object_vars)) {
					if (class_exists('Membership') && $user = Membership::getUser()) {
						if (method_exists($user, 'getId')) {
							$structure_object->updated_by = $user->getId();
						}
					}
				}
			}

			if ( ($this->isNewRecord($structure_object) && $this->getSaveType() != self::SAVE_TYPE_UPDATE) || $this->isForceInsert()) { // INSERT QUERY

				// Update this record with the owner's info
				if (array_key_exists('created', $structure_object_vars) && is_null($structure_object->created)) {
					$structure_object->created = date('Y-m-d H:i:s');
					if (array_key_exists('created_by', $structure_object_vars) && is_null($structure_object->created_by)) {
						if (class_exists('Membership') && $user = Membership::getUser()) {
							if (method_exists($user, 'getId')) {
								$structure_object->created_by = $user->getId();
							}
						}
					}
				}

				$insert_columns = array();
				$insert_values = array();
				foreach($this->updateFields as $field) {
					if ( isset($structure_object->$field) && !is_null($structure_object->$field) ) {
						$insert_columns[] = "`".$field."`";
						$insert_values[] = "'" . $this->safeString($structure_object->$field) . "'";
					}
				}

				// If this is a forced insert, make sure to insert the primary key
				if ($this->isForceInsert()) {
					if (is_array($primary_key)) {
						foreach($primary_key as $temp_primary_key) {
							array_push($insert_columns, "`" . $temp_primary_key . "`");
							array_push($insert_values, "'" . $this->safeString($structure_object->$temp_primary_key) . "'");
						}
					} else {
						if (!empty($primary_key)) {
							array_push($insert_columns, "`" . $primary_key . "`");
							array_push($insert_values, "'" . $this->safeString($structure_object->$primary_key) . "'");
						}
					}
				}

				$sql = sprintf($this->insertQuery, $this->tableName, implode(', ', $insert_columns), implode(', ', $insert_values));

				$result = $this->commandQuery($sql);

				// Populate primary key, but only if this is not a forced inserted record
				if (!is_array($primary_key) && !$this->isForceInsert() && !empty($primary_key)) {
					// Make sure result is not false (failed), otherwise a primary key could be associated with this record from a previously saved record
					if ($result !== false) $structure_object->$primary_key = mysqli_insert_id($this->getWriteConnection());
				}

			} else { // UPDATE QUERY
				$update_fields = array();
				foreach($this->updateFields as $field) {
					if ( isset($structure_object->$field) && !is_null($structure_object->$field) ) {
						$update_fields[] = "`".$field."` = '".$this->safeString($structure_object->$field)."'";
					}
				}

				if (is_array($this->primaryKey)) {
					$where_clauses = array();
					foreach($this->primaryKey as $key) {
						array_push($where_clauses, '`' . $key . '`' . "='" . $structure_object->$key . "'");
					}
					$where_clause = implode(' AND ', $where_clauses);
				} else {
					$where_clause = '`' . $this->primaryKey . '`' . "='" . $structure_object->$primary_key . "'";
				}

				$sql = sprintf($this->updateQuery, $this->getTableName(), implode(',', $update_fields), $where_clause);

				$this->commandQuery($sql);
			}
		}

		return $structure_object;
	}

	public function delete($primary_key) {
		#if (is_numeric($primary_key)) {
			$where = $this->primaryKey . " = '" . $this->safeString($primary_key) . "'";
			$this->commandQuery(sprintf($this->deleteQuery, $this->tableName, $where));
			return true;
		#} else return false;
	}

	public function getSearchQueryString(DAOSearch $dao_search_obj) {
		$select_columns = $dao_search_obj->getSelectColumns();
		if (count($select_columns) == 0) {
			$select_columns[] = $dao_search_obj->getTableKey() . '.*';
		}

		$from 		= DatabaseManager::getTable($dao_search_obj->getTableKey()) . ' AS ' . $dao_search_obj->getTableAlias();

		#$search_joins = $this->searchJoins;
		$search_joins = $dao_search_obj->getJoins();

		$join_string = '';
		#foreach($search_joins as $table_key=>$join_info) {
		foreach($search_joins as $search_join) {

			if ($search_join->shouldSelectAllFields()) {
				$select_columns[] = $search_join->getTableAlias() . '.*';
			} else {

				/**
				 * Join fields can be in the format:
				 * array(
				 *	array('column_name', 'column_name_alias')
				 * )
				 * OR (preferred):
				 * array(
				 * 	'column_name' => 'column_name_alias'
				 * )
				 **/
				$join_fields = $search_join->getSelectFields();

				foreach($join_fields as $join_field_index=>$join_field) {
					$join_field_alias = '';
					if (is_array($join_field)) {
						if (count($join_field) < 2) {
							$join_field = $join_field[0];
						} else {
							list($join_field, $join_field_alias) = $join_field;
						}
					} else if (!is_numeric($join_field_index)) { // 'column_name' => 'column_name_alias'
						$join_field_alias = $join_field;
						$join_field = $join_field_index;
					}
					$select_columns[] = $search_join->getTableAlias() . '.`' . $join_field . '`';
					if (!empty($join_field_alias)) $select_columns[count($select_columns)-1] .= ' AS ' . $join_field_alias;
				}
			}
			/*
			if (isset($join_info['columns'])) {
				$select_columns .= ', ' . $join_info['columns'];
			}*/

			/*
			$join_type = 'INNER';

			if (isset($join_info['join_type']) && strlen($join_info['join_type']) > 0) {
				$join_type = strtoupper($join_info['join_type']);
			}

			if (isset($join_info['join_criteria'])) {
				$from  .= ' ' . $join_type . ' JOIN ' . DatabaseManager::getTable($table_key) . ' AS ' . $table_key . ' ON ' . $join_info['join_criteria'];
			}
			*/
			#$from .= ' ' . $search_join->getJoinType() . ' JOIN ' . DatabaseManager::getTable($search_join->getTableKey()) . ' AS ' . $search_join->getTableKey() . ' ON ' . $join_info['join_criteria'];
			$join_criteria = array();
			$join_fields = $search_join->getJoinFields();
			foreach($join_fields as $join_table_field=>$core_table_field) {
				array_push($join_criteria, $join_table_field . ' = ' . $core_table_field);
			}

			$from .= ' ' . $search_join->getJoinType() . ' JOIN ' . DatabaseManager::getTable($search_join->getTableKey()) . ' AS ' . $search_join->getTableAlias() . ' ON ' . implode(' AND ', $join_criteria);
		}

		$where_string = $dao_search_obj->getWhereString();
		if (empty($where_string)) $where_string = '1=1';

		if ($dao_search_obj->makeDistinct()) $select_columns[0] = 'DISTINCT ' . $select_columns[0];

		$sql = sprintf($this->selectQuery, implode(', ', $select_columns), $from, $where_string);

		// Sorting
		$sorts = $dao_search_obj->getSorts();

		if (count($sorts) > 0) {

			#$sql .= 'ORDER BY ';
			$sort_stack = array();
			foreach($sorts as $sort) {
				$table_key	= $sort['TABLE_KEY'];
				$column		= $sort['COLUMN'];
				$direction	= $sort['DIRECTION'];

				$sort_str = '`' . $table_key . '`.' . $column;
				if (!empty($direction)) $sort_str .= ' ' . $direction;

				array_push($sort_stack, $sort_str);
			}

			if (count($sort_stack) > 0) $sql .= " ORDER BY " . implode(', ', $sort_stack);
		}

		$groups = $dao_search_obj->getGroups();

		if (count($groups) > 0) {
			$sql .= " GROUP BY " . implode(', ', array_map(function($group) {
				$table_key = $group['TABLE_KEY'];
				$column = $group['COLUMN'];
				$escape = $group['ESCAPE'];
				if ($escape) {
					$table_key = '`' . $table_key . '`';
					$column = '`' . $column . '`';
				}
				return $table_key . '.' . $column;
			}, $groups));
		}

		// Paging
		$current_page		= $dao_search_obj->getCurrentPage();
		$results_per_page	= $dao_search_obj->getResultsPerPage();

		if (!empty($results_per_page)) {
			if (empty($current_page) || !is_numeric($current_page)) {
				$current_page = 1;
			}
			$this->paginate($current_page, $results_per_page);
		}
		return $sql;
	}
	public function search($dao_search_obj) {
		$sql = $this->getSearchQueryString($dao_search_obj);
		return $this->selectQuery($sql, $this->modelName);
	}
	/**
	 * @param string $sql
	 * @param string $cast_as_class
	 * @return ResultSet
	 */
	public function selectQuery($sql, $cast_as_class='stdClass') { // SELECT statements; Returns result object
		$debug_message = '';

		$start_query_time = FrameworkManager::getTime();
		$query_key = 'query_'.md5($sql);

		if (isset($GLOBALS[$query_key]) && $this->isCachingResults()) {

			$cached_result_set = $GLOBALS[$query_key];
			$cached_result_set->resetIndex(); // Make sure that pointer is reset to the first record
			DebugDBManager::addMessage($sql . ' (Return cached)');
			return $cached_result_set; // Cache result set

		}

		// Initialize result_set
		$result_set = new ResultSet();

		try {
			$db = $this->getReadConnection();
		} catch (MissingConnectionException $e) {
			$this->addError($e->getMessage());
			return $result_set;
		}

		/**
		 * Query Total Number of Results
		 */
		$check_pagination_group = false;
		if ($this->_paginate) { //if (!is_null($paginate)) {
			$sql_total_results = preg_replace("/SELECT.*FROM/ms", "SELECT COUNT(*) AS total_results FROM", $sql);
			$sql_total_results = preg_replace('/ORDER BY.*/ms', '', $sql_total_results);

			$is_group = preg_match('/GROUP BY (.*)/ms', $sql);
			//SQL_CALC_FOUND_ROWS, FOUND_ROWS()
			if ($is_group) {
				$sql = trim($sql);
				if (substr($sql, 0, 6) == 'SELECT') {
					$sql = "SELECT SQL_CALC_FOUND_ROWS " . substr($sql, 6);
					$check_pagination_group = true;
				}

			} else {
				$total_results_query = mysqli_query($db, $sql_total_results);
				$total_results_obj = mysqli_fetch_object($total_results_query);
				$result_set->setTotalResults($total_results_obj->total_results);
			}
			$result_set->setCurrentPage($this->_currentPage);
			$result_set->setResultsPerPage($this->_resultsPerPage);
			$sql .= ' LIMIT ' . ($this->_currentPage * $this->_resultsPerPage - $this->_resultsPerPage) . ', ' . $this->_resultsPerPage;
		}

		if ($query = mysqli_query($db, $sql)) {

			$fields = mysqli_fetch_fields($query);

			while ($result = mysqli_fetch_object($query)) {

				$row = ($this->separateResultTables()) ? new stdClass() : new $cast_as_class;

				foreach($fields as $field_info) {

					$table = $field_info->table;
					$field = $field_info->name;

					$field_context = $row;
					if ($this->separateResultTables()) {
						if (!isset($row->$table)) $row->$table = new stdClass();
						$field_context = $row->$table;
					}

					$field_context->$field = $result->$field;

				}

				$result_set->add($row);

			}

			if ($check_pagination_group) {

				if ($query = mysqli_query($db, "SELECT FOUND_ROWS() AS total_results")) {
					$row = mysqli_fetch_object($query);
					$result_set->setTotalResults($row->total_results);
				}

			}

			if ($this->isCachingResults()) {
				$GLOBALS[$query_key] = $result_set;
			}

		} else {
			$d = new Dictionary(array('sql'=>$sql, 'error'=>mysqli_error($db)));
			Custodian::log('database', 'SQL: ${sql}. Error: ${error}', $d);
		}

		$end_query_time = FrameworkManager::getTime();
		$query_time = $end_query_time - $start_query_time;
		$display_query_time = $query_time;
		if ($query_time > 0.10) $display_query_time = '<span style="color:#cc0000;font-weight:bold;">' . $display_query_time . '</span>';

		$debug_message .= $sql;
		if ($this->isCachingResults()) $debug_message .= ' (Caching: true)';
		else $debug_message .= ' (Caching: false)';
		$debug_message .= ' (Query Time: '.$display_query_time.')';

		DebugDBManager::addMessage($debug_message);
		DebugDBManager::addTime($query_time);
		return $result_set;
	}

	public function commandQuery($query) { // UPDATE, DELETE, INSERT;
		$debug_message = '';

		$start_query_time = FrameworkManager::getTime();

		try {
			$db = $this->getWriteConnection();
		} catch (MissingConnectionException $e) {
			$this->addError($e->getMessage());
			return false;
		}

		if (!@mysqli_query($db, $query)) {
			// Custodian error
			$d = new Dictionary(array('sql'=>$query, 'error'=>mysqli_error($db)));
			Custodian::log('database', 'SQL: ${sql}. Error: ${error}', $d);
			// Class error
			$this->addError(mysqli_error($db));
			return false;
		}

		// Debug Info
		$end_query_time = FrameworkManager::getTime();
		$query_time = $end_query_time - $start_query_time;
		$display_query_time = $query_time;
		if ($query_time > 0.10) $display_query_time = '<span style="color:#cc0000;font-weight:bold;">' . $display_query_time . '</span>';

		$debug_message .= $query;

		$debug_message .= ' (Query Time: '.$display_query_time.')';
		DebugDBManager::addMessage($debug_message);
		DebugDBManager::addTime($query_time);
		return true;
	}

	/**
	 * Paginate results
	 */
	public function paginate($current_page=null, $results_per_page) {
		$this->_paginate = true;
		if (!is_null($current_page)) $this->_currentPage = $current_page;
		if (!is_null($results_per_page)) $this->_resultsPerPage = $results_per_page;
	}

	/**
	 * Checks if the structure represents a new entry.  Checks if the primary key is empty
	 * Assumes only one primary key - needs to be updated if multiple primary keys are specified
	 */
	public function isNewRecord($structure_object) {
		$primary_key = $this->primaryKey;
		if (is_array($primary_key)) { // This probably still needs some work, since if the primary key is an array, we might need to do some additional checking to verify that we should really return true, right now we assume that all fields must not be empty
			$any_empty = false;
			foreach($primary_key as $key) {
				$primary_key_value = $structure_object->$key;
				if (strlen($primary_key_value) == 0) $any_empty = true; // Check if any primary key value is empty, if so, mark this as a new record
			}
			return $any_empty;
		} else {
			if (!empty($primary_key)) {
				$primary_key_value = $structure_object->$primary_key;
				return (strlen($primary_key_value) == 0);
			} else {
				return true;
			}
		}
	}

	/**
	 * Whether or not to use cached queries
	 */
	public function setCacheResults($cache_results) { // Boolean
		$this->_cacheResults = $cache_results;
	}

	public function isCachingResults() {
		$DATABASE_CACHE_RESULTS = ConfigurationManager::get('DATABASE_CACHE_RESULTS');

		$config_cache_results = !($DATABASE_CACHE_RESULTS == 'false' || $DATABASE_CACHE_RESULTS === false);
		$caching = ($this->_cacheResults && $config_cache_results);
		return $caching;
	}

	/**
	 * Whether or not to force INSERT instread of UPDATE
	 * A lot of code still relies on this method, so it is kept for legacy support
	 */
	public function setForceInsert($force_insert) { // Boolean
		#$this->_forceInsert = $force_insert;
		// Added the following for legacy support so that we can support the new setSaveType method functionality
		if ($force_insert) $this->setSaveType(self::SAVE_TYPE_INSERT);
		else $this->setSaveType(self::SAVE_TYPE_AUTO);
	}
	public function setSaveType($save_type) {
		$allowed_types = array(self::SAVE_TYPE_AUTO, self::SAVE_TYPE_INSERT, self::SAVE_TYPE_UPDATE);
		if (!in_array($save_type, $allowed_types)) throw new Exception('Save type ' . $save_type . ' is not a valid save type option');
		$this->_saveType = $save_type;
	}
	public function getSaveType() {
		$save_type = $this->_saveType;
		if (empty($save_type)) $save_type = self::SAVE_TYPE_AUTO;
		return $save_type;
	}

	#function isForceInsert() { return $this->_forceInsert; }
	public function isForceInsert() { return ($this->getSaveType() == self::SAVE_TYPE_INSERT); }

	/**
	 * Make query value safe for database
	 * @param string $string A string to make database safe
	 * @access public static
	 * @static
	 */
	public static function safeString($string, $connectionName=null) {
		$return_string = $string;

		try {
			$db = ConnectionManager::getConnection($connectionName);
			$return_string = mysqli_real_escape_string($db, $return_string);
		} catch (MissingConnectionException $e) {
			// Do nothing
		}

		return $return_string;
	}

	/**
	 * Check if a database table exists
	 */
	public function tableExists($table_name) {
		$sql_select = "SHOW TABLES LIKE '" . $table_name . "'";
		$results = $this->selectQuery($sql_select);
		if ($results->getAt(0)) return true;
		else return false;
	}
	/**
	 * Get all tables
	 */
	public function getDatabaseTables($connectionName=null) {
		$sql_select = "SHOW TABLES";
		$rs_results = new ResultSet();

		$db = ConnectionManager::getConnection($connectionName === null ? $this->getReadConnectionName() : $connectionName);

		if ($query = mysqli_query($db, $sql_select)) {
			$field_names = array();

			if (mysqli_num_fields($query) == 1) {

				while($fetch_row = mysqli_fetch_row($query)) {
					$result = new stdClass();
					$result->name = $fetch_row[0];
					$rs_results->add($result);
				}

			}

		}

		return $rs_results;
	}
	/**
	 * Check if there are any errors on the stack
	 */
	public function anyErrors() {
		return (count($this->errors) > 0);
	}
	public function addError($message) {
		array_push($this->errors, $message);
	}
	public function getErrors() {
		return $this->errors;
	}

	public function setModelName($model_name) { $this->modelName = $model_name; }
	public function getModelName() { return $this->modelName; }

	public function separateResultTables($true_false=null) {
		if (is_null($true_false)) { // Getter
			return $this->separateResultTables;
		} else { // Setter
			$this->separateResultTables = $true_false;
		}
	}
	public function addUpdateField($field_name) {
		array_push($this->updateFields, $field_name);
	}
	public function getUpdateFields() { return $this->updateFields; }
	public function getPrimaryKey() { return $this->primaryKey; }

	public function setPrimaryKey($key) {
		$this->primaryKey = $key;
	}
	public function setPrimaryKeys($array_keys=array()) {
		$this->primaryKey = $array_keys;
	}

	public function addPrimaryKey($field_name) {
		if (!is_array($this->primaryKey) && !empty($this->primaryKey)) {
			throw new Exception('Trying to add a primary key to a data access object that already has a primary key.');
		}
		if (!is_array($this->primaryKey)) $this->primaryKey = array();
		array_push($this->primaryKey, $field_name);
	}

	/**
	 * @return mixed A database connection for reading
	 */
	public function getReadConnection() {
		return ConnectionManager::getConnection($this->readConnName);
	}

	/**
	 * @return mixed A database connection for writing
	 */
	public function getWriteConnection() {
		return ConnectionManager::getConnection($this->writeConnName);
	}

	/**
	 * @return string|null
	 */
	public function getWriteConnectionName()
	{
		return $this->writeConnName;
	}

	/**
	 * @param string $writeConnName
	 */
	public function setWriteConnectionName($writeConnName)
	{
		$this->writeConnName = $writeConnName;
	}

	/**
	 * @return string
	 */
	public function getReadConnectionName()
	{
		return $this->readConnName;
	}

	/**
	 * @param string $readConnName
	 */
	public function setReadConnectionName($readConnName)
	{
		$this->readConnName = $readConnName;
	}

	/**
	 * Convenience method to set read/write connection names at the same time
	 * @param $connName
	 */
	public function setConnectionName($connName) {
		$this->setWriteConnectionName($connName);
		$this->setReadConnectionName($connName);
	}
}

/**
 * Takes database formatted string and converts to timestamp
 * @param $database_format string in the format yyyy-mm-dd hh:ii:ss
 * @return timestamp
 */

function database_date_to_timestamp($database_format) {
	return strtotime($database_format);
	$hour	= 0;
	$minute	= 0;
	$second	= 0;
	$month	= null;
	$day	= null;
	$year	= null;

	$date_time = explode(' ', $database_format);
	$date = $date_time[0];
	if (preg_match('/([0-9]{4})-([0-9]{2})-([0-9]{2})/', $date, $matches)) {
		$year	= $matches[1];
		$month	= $matches[2];
		$day	= $matches[3];

		if (isset($date_time[1])) { // Time
			$time = $date_time[1];
			if (preg_match('/([0-9]{2}):([0-9]{2}):([0-9]{2})/', $time, $matches)) {
				$hour		= $matches[1];
				$minutes	= $matches[2];
				$seconds	= $matches[3];
			} else return false;
		}
	} else return false;
	$mktime = mktime($hour, $minute, $second, $month, $day, $year);
	return $mktime;
}

/**
 * Takes database formatted string and converts to formats it using date()
 * @param $display_format the format to use for date($display_format)
 * @param $database_format string in the format yyyy-mm-dd hh:ii:ss
 * @param $default_value the value to return if the date string is empty or invalid
 * @return string date formatted string
 */

function database_format_date($display_format, $database_format, $default_value=false) {
	if ($mktime = database_date_to_timestamp($database_format)) {
		return date($display_format, $mktime);
	} else {
		return $default_value;
	}
}
