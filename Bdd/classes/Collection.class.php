<?php

class Collection {
	private $fields;
	private $tables;
	private $joint;
	private $where;
	private $order;
	private $group;
	private $params;
	private $limit;
	private $distinct;


	protected function __construct() {
		$this->fields = array();
		$this->tables = array();
		$this->joint = array();
		$this->where = array();
		$this->order = array();
		$this->group = array();
		$this->params = array();
		$this->limit = NULL;
		$this->distinct = False;
	}


	public static function Query($from=NULL) {
		$c = new self();
		if ($from !== NULL)
		  $c->from($from);
		return $c;
	}


	public function limit($limit) {
		$this->limit = $limit;
		return $this;
	}


	public function distinct() {
		$this->distinct = True;
		return $this;
	}


	public function select() {
		$args = func_get_args();
		if (count($args) == 1 && is_array($args[0]))
			$args = $args[0];

		$this->fields = array_merge($this->fields, $args);
		return $this;
	}


	public function resetSelect() {
		$this->fields = array();
		return $this;
	}


	public function selectAs($field, $name) {
		$this->fields[$name] = $field;
		return $this;
	}


	public function from() {
		$args = func_get_args();
		if (count($args) == 1 && is_array($args[0]))
			$args = $args[0];

		$this->tables = array_merge($this->tables, $args);
		return $this;
	}


	public function leftJoin($table, $filter) {
		$this->joint[] = "LEFT JOIN $table ON $filter";
		return $this;
	}

	public function where() {
		$args = func_get_args();
		if (count($args) == 1 && is_array($args[0]))
			$args = $args[0];

		$this->where = array_merge($this->where, $args);
		return $this;
	}


	public function whereEq($name, $value) {
		$bdd = Bdd::getInstance();

		if (is_string($value))
			$this->where[] = $name . "=" . $bdd->quote($value);
		elseif ($value === NULL)
			$this->where[] = $name . " IS NULL";
		elseif ($value === True)
			$this->where[] = $name . "=1";
		elseif ($value === False)
			$this->where[] = $name . "=0";
		else
			$this->where[] = $name . "=" . $value;

		return $this;
	}


	public function filter($value, $operator="=") {
		$bdd = Bdd::getInstance();
		$value = $bdd->quote($value);
		
		$filter = array();
		foreach ($this->fields as $field) {
			$filter[] = $bdd->quoteIdent($field) . " " . $operator . " " . $value;
		}
		$this->where[] = implode(" OR ", $filter);
	}


	public function groupBy() {
		$args = func_get_args();
		if (count($args) == 1 && is_array($args[0]))
			$args = $args[0];

		$this->group = array_merge($this->group, $args);
		return $this;
	}


	public function orderBy() {
		$args = func_get_args();
		if (count($args) == 1 && is_array($args[0]))
			$args = $args[0];

		$this->order = array_merge($this->order, $args);
		return $this;
	}


	public function with($params) {
		$this->params = array_merge($this->params, $params);
		return $this;
	}


	public function withValue($param, $value) {
		$this->params[$param] = $value;
		return $this;
	}


	public function getQuery($pos = 0) {
		$query = "SELECT ".($this->distinct ? "DISTINCT ":"");

		if (count($this->fields) == 0)
			$query .= "*";
		else {
			$fields = array();
			foreach($this->fields as $k=>$v) {
				if (is_int($k))
					$fields[] = $v;
				else
					$fields[] = "$v as $k";
			}
			$query .= implode(", ", $fields);

		}

		$query .= " FROM ".implode(", ", $this->tables);

		if (count($this->joint) > 0)
			$query .= " ".implode(" ", $this->joint);

		if (count($this->where) > 0)
			$query .= " WHERE (".implode(") AND (", $this->where).")";

		if (count($this->group) > 0)
			$query .= " GROUP BY ".implode(", ", $this->group);

		if (count($this->order) > 0)
			$query .= ' ORDER BY '. implode(", ", $this->order);

		if ($this->limit)
			$query .= ' LIMIT ' . ($pos * $this->limit) .", " . $this->limit;

		return $query;
	}


	public function getValues($pos = 0) {
		$bdd = Bdd::getInstance();
		$sql = $this->getQuery($pos);
		$result = $bdd->query($sql, $this->params);
		return $result;
	}


	public function getValuesArray($pos = 0) {
		$collection = array();
		$result = $this->getValues($pos);
		foreach ($result as $row) {
			$collection[] = $row;
		}
		return $collection;
	}

}
