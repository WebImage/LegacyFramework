<?php
class DAOJoin {
	const JOIN_INNER	= 'INNER';
	const JOIN_LEFT		= 'LEFT';
	const JOIN_RIGHT	= 'RIGHT';
	/**
	 * Each $selectFields value can be an array if the field needs to be an alias, i.e. array('field_name', 'field_alias')
	 **/
	private $tableKey, $tableAlias, $joinType, $joinFields=array(), $selectFields=array();
	/**
	 * @param string $tableKey The table key/model name to select
	 * @param string $joinType The type of join to use (LEFT, RIGHT, CENTER)
	 * @param array of string $join_fields the columns to include
	 * @param array of string $select_fields the columns to include in the select field
	 **/
	function __construct($table_key, $join_type=null, $join_fields=null, $select_fields=null) {
		
		if (is_null($join_type)) $join_type = DAOJoin::JOIN_INNER;
		else {
			$join_type = strtoupper($join_type);
			if (!in_array($join_type, array(DAOJoin::JOIN_INNER, DAOJoin::JOIN_LEFT, DAOJoin::JOIN_RIGHT))) throw new Exception('join_type ' . $join_type . ' is invalid');
		}
		if (!is_array($join_fields)) throw new Exception('join_fields parameter should be of type array');
		if (!is_null($select_fields)) {
			$valid = false;
			if (is_string($select_fields) && $select_fields == DAOSearch::ALL_FIELDS) $valid = true;
			if (!$valid && !is_array($select_fields)) throw new Exception('select_fields parameter should be of type array');
		}
		
		if (is_array($table_key)) {
			$this->tableKey = $table_key[0];
			$this->tableAlias = $table_key[1];
		} else {
			$this->tableKey = $table_key;
			$this->tableAlias = $table_key;
		}

		$this->joinType = $join_type;
		$this->joinFields = $join_fields;
		
		if (!is_null($select_fields)) $this->selectFields = $select_fields;
	}
	public function getTableKey() { return $this->tableKey; }
	public function getTableAlias() { return $this->tableAlias; }
	public function getJoinType() { return $this->joinType; }
	public function getJoinFields() { return $this->joinFields; }
	public function getSelectFields() { return $this->selectFields; }
	public function shouldSelectAllFields() { return (is_string($this->selectFields) && $this->selectFields == DAOSearch::ALL_FIELDS); }
	
}
class DAOSearch {
	const JOIN_INNER	= 'INNER'; // Kept for compatability - should use DAOJoin::JOIN_INNER
	const JOIN_LEFT		= 'LEFT'; // Kept for compatability - should use DAOJoin::JOIN_LEFT
	const JOIN_RIGHT	= 'RIGHT'; // Kept for compatability - should use DAOJoin::JOIN_RIGHT
	
	const SORT_ASC		= 'ASC';
	const SORT_DESC		= 'DESC';
	
	const ALL_FIELDS	= '*';

	private $selectColumns = [];

	private $tableKey;
	private $tableAlias;
	var $_fields = array();
	private $_sorts = array();
	private $_groups = array();
	var $_joins = array();
	private $joins = array();
	var $_currentPage = 1;
	var $_resultsPerPage;
	private $makeDistinct = false;
	/**
	 * @param string or array If string, table key and alias will both be set to passed param. If array is passed the first entry will be the table key and the second entry will be the alias
	 */
	function __construct($table_key, $current_page=null, $results_per_page=null) {
		if (is_array($table_key)) {
			if (count($table_key) == 1) { // key => alias
				$this->setTableKey(key($table_key));
				$this->setTableAlias(current($table_key));
			} else { // ['key', 'alias']
				$this->setTableKey($table_key[0]);
				$this->setTableAlias($table_key[1]);
			}
		} else {
			$this->setTableKey($table_key);
			$this->setTableAlias($table_key);
		}
		
		$this->setCurrentPage($current_page);
		$this->setResultsPerPage($results_per_page);
	}
	
	function addSearchField($search_field) {
		$this->_fields[] = $search_field;
	}
	
	// Alias for add sort
	function addOrder($table_key, $order_by_field, $direction=DAOSearch::SORT_ASC) {
		$this->addSort($table_key, $order_by_field, $direction);
	}
	function addSort($table_key, $order_by_field, $direction=DAOSearch::SORT_ASC) {
		$this->_sorts[] = array(
		   'TABLE_KEY'	=> $table_key,
		   'COLUMN'		=> $order_by_field,
		   'DIRECTION'	=> $direction
	   );
	}

	function addGroup($table_key, $group_by_field, $escape=true) {
		$this->_groups[] = array(
			'TABLE_KEY' => $table_key,
			'COLUMN' => $group_by_field,
			'ESCAPE' => $escape
		);
	}
	
	/**
	 * Add a complex join statement, primarily using SQL syntax
	 * @param string $join_table The table key for the table to be joined
	 * @param array $criteria_array An array of values to be used to join the table, e.g. array('foreign_table.foreign_key = primary_table.primary_key')
	 * @param string $join_type The type of join to be performed, defaults to DAOSearch::JOIN_INNER
	 * @return void
	 */
	/*
	function addComplexJoin($join_table, $criteria_array, $join_type=DAOSearch::JOIN_INNER, $fields=array()) {
		$this->_joins[] = array(
			'JOIN_TABLE' => $join_table,
			'JOIN_TYPE' => $join_type,
			'CRITERIA' => $criteria_array,
			'COLUMNS' => $columns
		);
	}
	*/
	function addJoin($join_table, $join_table_field_key=null, $local_key=null, $join_type=DAOSearch::JOIN_INNER) {		
		if (is_a($join_table, 'DAOJoin')) {
			array_push($this->joins, $join_table);
		} else {
			$join_table_key = $join_table;
			if (is_array($join_table_key)) $join_table_key = $join_table_key[1];
			$join = new DAOJoin($join_table, $join_type, array($join_table_key . '.' . $join_table_field_key => $this->getTableKey() . '.' . $local_key));
			array_push($this->joins, $join);

		}
		/*
		$this->addComplexJoin($join_table,
			array(
				$join_table . '.' . $join_table_field_key . ' = ' . $this->getTableKey() . '.' . $local_key
			)
		);
		*/
	}

	public function addSelect($column) { $this->selectColumns[] = $column; }
	public function getTableKey() { return $this->tableKey; }
	public function getTableAlias() { return $this->tableAlias; }
	public function getJoins() { return $this->joins; }
	public function getSelectColumns() { return $this->selectColumns; }
	public function getSorts() { return $this->_sorts; }
	public function getGroups() { return $this->_groups; }
	public function getCurrentPage() { return $this->_currentPage; }
	public function getResultsPerPage() { return $this->_resultsPerPage; }
	
	public function getWhereString() {
		$fields = $this->_fields;
		$queries = array();
		foreach($fields as $field) {
			$query_string = $field->getQueryString();
			if (strlen($query_string) > 0) {
				$queries[] = $query_string;
			}
		}
		return implode(' AND ', $queries);
	}
	
	public function setTableKey($table_key) { $this->tableKey = $table_key; }
	public function setTableAlias($alias) { $this->tableAlias = $alias; }
	public function setCurrentPage($current_page) { if (is_numeric($current_page)) $this->_currentPage = $current_page; }
	public function setResultsPerPage($results_per_page) { if (is_numeric($results_per_page)) $this->_resultsPerPage = $results_per_page; }
	
	// Getter/setter
	public function makeDistinct($true_false=null) {
		if (is_null($true_false)) { // Getter
			return $this->makeDistinct;
		} else { // Setter
			$this->makeDistinct = $true_false;
		}
	}
}
/* Replacing with DAOSearchWhere
interface IDAOSearchField {
	//var $tableKey, $_fieldKey, $_value;
	public function __construct($table_key, $field_field, $value=null);
	public function getTableKey();
	public function getFieldKey();
	public function getQueryString();
	
	function setTableKey($table_key);
	function setFieldKey($field_key);
}*/

interface IDAOSearchWhere {
	public function getQueryString();
}

class DAOSearchGroup implements IDAOSearchWhere {
	var $_fields = array();
	public function addSearchField($field) {
		array_push($this->_fields, $field);
	}
	public function getQueries() {
		$fields = $this->_fields;
		$queries = array();
		foreach($fields as $field) {
			$query_string = $field->getQueryString();
			if (strlen($query_string) > 0) {
				$queries[] = $query_string;
			}
		}
		return $queries;
	}
	
	public function getQueryString() {
		$queries = $this->getQueries();
		return implode(' AND ', $queries);
	}
	
}

class DAOSearchOrGroup extends DAOSearchGroup {
	function getQueryString() {
		$queries = $this->getQueries();
		return '(' . implode(' OR ', $queries) . ')';
	}	
}

class DAOSearchField implements IDAOSearchWhere {
	var $_tableKey, $_fieldKey, $_value;
	function __construct($table_key, $field_key, $value=null) {
		$this->setTableKey($table_key);
		$this->setFieldKey($field_key);
		$this->setValue($value);
	}
	function getTableKey() { return $this->_tableKey; }
	function getFieldKey() { return $this->_fieldKey; }
	function getValue() { return $this->_value; }
	function getQueryString() {
		return $this->getTableKey() . "." . $this->getFieldKey() . " = '" . $this->getValue() . "'";
	}
	
	function setTableKey($table_key) { $this->_tableKey = $table_key; }
	function setFieldKey($field_key) { $this->_fieldKey = $field_key; }
	function setValue($value) { $this->_value = $value; }
	
}
class DAOSearchFieldWithOperator extends DAOSearchField {
	private $operator;

	function __construct($table_key, $field_key, $operator=null, $value=null) {
		parent::__construct($table_key, $field_key, $value);
		$this->operator = $operator;
	}

	protected function getOperator() { return $this->operator; }

	public function getQueryString() {
		return $this->getTableKey() . "." . $this->getFieldKey() . " " . $this->getOperator(). " '" . $this->getValue() . "'";
	}
}

abstract class AbstractDAOSearchFieldWithOperator extends DAOSearchFieldWithOperator {
	public function __construct($table_key, $field_key, $value) {
		parent::__construct($table_key, $field_key, null, $value);
	}
}
class DAOSearchFieldNot extends AbstractDAOSearchFieldWithOperator { protected function getOperator() { return '!='; } }
class DAOSearchFieldGreaterThan extends AbstractDAOSearchFieldWithOperator { protected function getOperator() { return '>'; } }
class DAOSearchFieldLessThan extends AbstractDAOSearchFieldWithOperator { protected function getOperator() { return '<'; } }
class DAOSearchFieldGreaterThanOrEqual extends AbstractDAOSearchFieldWithOperator { protected function getOperator() { return '>='; } }
class DAOSearchFieldLessThanOrEqual extends AbstractDAOSearchFieldWithOperator { protected function getOperator() { return '<='; } }
class DAOSearchFieldNull extends DAOSearchField { function getQueryString() { return $this->getTableKey() . "." . $this->getFieldKey() . ' IS NULL'; } }
class DAOSearchFieldNotNull extends DAOSearchField { function getQueryString() { return $this->getTableKey() . "." . $this->getFieldKey() . ' IS NOT NULL'; } }

// Strlen
class DAOSearchFieldLength extends DAOSearchField {
	protected function getQueryStringWithOperator($operator='=') {
		return "LENGTH(" . $this->getTableKey() . "." . $this->getFieldKey() . ") " . $operator . " '" . $this->getValue() . "'";
	}
	public function getQueryString() { return $this->getQueryStringWithOperator(); }
}
class DAOSearchFieldLengthGreaterThan extends DAOSearchFieldLength { function getQueryString() { return $this->getQueryStringWithOperator('>'); } }
class DAOSearchFieldLengthLessThan extends DAOSearchFieldLength { function getQueryString() { return $this->getQueryStringWithOperator('<'); } }
class DAOSearchFieldLengthGreaterThanOrEqual extends DAOSearchFieldLength { function getQueryString() { return $this->getQueryStringWithOperator('>='); } }
class DAOSearchFieldLengthLessThanOrEqual extends DAOSearchFieldLength { function getQueryString() { return $this->getQueryStringWithOperator('<='); } }

class DAOSearchFieldWildcard extends DAOSearchField {
	var $_value, $_wildcard;
	function __constructFieldWildcard($table_key, $field_key, $value, $wildcard=false) {
		parent::__construct($table_key, $field_key, $value);
		$this->isWildcard($wildcard);
	}
	
	function isWildcard($is_wild_card=null) {
		if (is_null($is_wild_card)) {
			return $this->_wildcard;
		} else {
			$this->_wildcard = $is_wild_card;
		}
	}
	
	function getQueryString() {
		return $this->getTableKey() . "." . $this->getFieldKey() . " LIKE '%" . DataAccessObject::safeString($this->getValue()) . "%'";
	}
}
class DAOSearchFieldValues extends DAOSearchField {
	private $values=array();
	function __construct($table_key, $field_key, $values) {
		parent::__construct($table_key, $field_key);
		if (is_array($values)) $this->values = $values;
	}
	public function getQueryString() {
		$values = array();
		foreach($this->values as $value) {
			array_push($values, DataAccessObject::safeString($value) );
		}
		return $this->getTableKey() . '.' . $this->getFieldKey() . " IN ('" . implode("', '", $values) . "')";
	}
}

class DAOSearchFieldNotValues extends DAOSearchField {
	private $values=array();
	function __construct($table_key, $field_key, $values) {
		parent::__construct($table_key, $field_key);
		if (is_array($values)) $this->values = $values;
	}
	public function getQueryString() {
		$values = array();
		foreach($this->values as $value) {
			array_push($values, DataAccessObject::safeString($value) );
		}
		return $this->getTableKey() . '.' . $this->getFieldKey() . " NOT IN ('" . implode("', '", $values) . "')";
	}
}
class DAOSearchFieldRange extends DAOSearchField {
	var $_lowValue, $_highValue;
	function __construct($table_key, $field_key, $low_value=null, $high_value=null) {
		parent::__construct($table_key, $field_key);
		$this->setLowValue($low_value);
		$this->setHighValue($high_value);
	}
	
	function getLowValue() { return $this->_lowValue; }
	function getHighValue() { return $this->_highValue; }

	function setLowValue($low_value) { $this->_lowValue = $low_value; }
	function setHighValue($high_value) { $this->_highValue = $high_value; }
	
	function getQueryString() {
		$low_value = $this->getLowValue();
		$high_value = $this->getHighValue();
		
		$query = '';
		if (!empty($low_value) && !empty($high_value)) {
			$query .= " BETWEEN " . $low_value . " AND " . $high_value;
		} else if (!empty($low_value)) {
			$query .= " > '" . $low_value . "'";
		} else if (!empty($high_value)) {
			$query .= " < '" . $high_value . "'";
		}
		
		if (!empty($query)) $query = $this->getTableKey() . '.' . $this->getFieldKey() . $query;
		return $query;
	}
	
}
