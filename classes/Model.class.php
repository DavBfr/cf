<?php

abstract class Model {

	protected $table;
	protected $fields;
	protected $bdd;


	function __construct() {
		$this->bdd = Bdd::getInstance();

		list($this->table, $this->fields) = $this->getTable();

		foreach ($this->fields as $name => $prop) {
			$defaults = $this->getDefaults($this->table, $name);
			$this->fields[$name] = array_merge($defaults, $prop);
		}
	}


	protected abstract function getTable();


	public function getTableName() {
		return $this->table;
	}


	public function getFields() {
		return $this->fields;
	}


	public function getDefaults($table, $field) {
		return array(
			"type"=>"int",
			"foreign"=>NULL,
			"display"=>$table.".".$field,
			"name"=>$table."_".$field,
			"caption"=>ucwords(str_replace("_", " ", $field)),
			"null"=>false,
			"edit"=>true,
			"list"=>false,
			"primary"=>false,
			"autoincrement"=>false,
		);
	}


	public function createTable() {
		return $this->bdd->createTable($this->table, $this->fields);
	}


	public function build_query($fields, $tables, $where = array(1), $limit=NULL, $order=NULL) {
		$query = "SELECT ";
		$query .= implode(", ", $fields);
		$query .= " FROM ";
		$query .= implode(", ", $tables);
		$query .= " WHERE (";
		$query .= implode(") AND (", $where);
		$query .= ")";
		if ($order) {
			$query .= ' ORDER BY '. implode(", ", $order);
		}
		if ($limit) {
			$query .= ' LIMIT '. implode(", ", $limit);
		}
		return $query;
	}


	public function make_filter($fieldname, $value) {
		if ($this->fields[$fieldname]["type"] == "text")
			return $this->table.".$fieldname LIKE ".$this->bdd->quote("%".$value."%");
		return $this->table.".$fieldname = ".$this->bdd->quote($value);
	}


	public function make_global_filter($value) {
		$where = array();
		foreach($this->fields as $name => $prop) {
			if ($this->fields[$name]["type"] == "text")
				$where[] = $this->make_filter($name, $value);
		}
		return "(" . implode(") OR (", $where) . ")";
	}


	public function get_list($filtres = array()) {
		$fields = array();
		$tables = array($this->table);
		$where = array("1");
		if (isset($filtres["Q"]))
			$where[] = $this->make_global_filter($filtres["Q"]);

		if (isset($filtres["L"]))
			$limit = explode(",", $filtres["L"]);
		else
			$limit = NULL;

		foreach($this->fields as $name => $prop) {
			if ($prop["list"] || $prop["primary"]) {
				$fields[] = $prop["display"] . " as " . $prop["name"];
			}
			if ($prop["foreign"] != null) {
				list($table, $field) = explode(".", $prop["foreign"]);
				$tables[] = $table;
				$where[] = $prop["foreign"] . "=" . $this->table.".".$name;
				$fields[] = $this->table.".".$name . " as " . $this->table."_".$name;
			}
			if (isset($filtres[$prop["display"]])) {
				$where[] = $this->make_filter($name, $filtres[$prop["display"]]);
			}
			elseif (isset($filtres[$name])) {
				$where[] = $this->make_filter($name, $filtres[$name]);
			}
			elseif (isset($filtres[$prop["name"]])) {
				$where[] = $this->make_filter($name, $filtres[$prop["name"]]);
			}
		}
		return array($fields, $tables, $where, $limit);
	}

}
