<?php

class Collection {
	private $fields;
	private $tables;
	private $where;
	private $order;
	private $params;
	private $limit;


	protected function __construct() {
		$this->fields = array();
		$this->tables = array();
		$this->where = array(1);
		$this->order = array();
		$this->params = array();
		$this->limit = 50;
	}


	public static function Query() {
		$c = new self();
		return $c;
	}


	public function limit($limit) {
		$this->limit = $limit;
		return $this;
	}


	public function select() {
		$this->fields = array_merge($this->fields, func_get_args());
		return $this;
	}
	
	
	public function from() {
		$this->tables = array_merge($this->tables, func_get_args());
		return $this;
	}


	public function where() {
		$this->where = array_merge($this->where, func_get_args());
		return $this;
	}
	
	
	public function orderBy() {
		$this->order = array_merge($this->order, func_get_args());
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


	public function getValues($pos = 0) {
		$bdd = Bdd::getInstance();
		if (count($this->fields) == 0) {
			$fields = "*";
		} else {
			$fields = $this->fields;
		}
		$sql = $bdd->select(
			$fields,
			$this->tables,
			$this->where,
			array($pos * $this->limit, $this->limit),
			$this->order);
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
