<?php

class Model {

	protected $table;
	protected $fields;


	function __construct() {
		list($this->table, $this->fields) = $this->getTable();
		foreach ($this->fields as $name => $prop) {
			$defaults = $this->getDefaults($this->table, $name);
			$this->fields[$name] = array_merge($defaults, $prop);
		}
	}


	protected function getTable() {
		$table = get_class($this);
		if (substr($table, -5) == "Model") {
			return $this->getFromConfig("model." . strtolower(substr($table, 0, -5)));
		}
	}


	public function getFromConfig($key) {
		$config = Config::getInstance();
		return array(substr($key, strrpos($key, ".") + 1), $config->get($key));
	}


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
			"default"=>NULL,
			"list"=>false,
			"primary"=>false,
			"autoincrement"=>false,
		);
	}


	public function createTable() {
		$bdd = Bdd::getInstance();
		return $bdd->createTable($this->table, $this->fields);
	}


	public function newRow() {
		return new ModelData($this);
	}


	public function simpleSelect($where=array(1), $params=array(), $limit=NULL, $order=NULL) {
		$bdd = Bdd::getInstance();
		$query = $bdd->select(array_map(array($bdd, "quoteIdent"), array_keys($this->fields)), $bdd->quoteIdent($this->table), $where, $limit, $order);
		$this->simpleselect = $bdd->query($query, $params);
		return new ModelData($this, $this->simpleselect);
	}


	public function getPrimaryKey($id) {
		foreach($this->fields as $key=>$val) {
			if ($val["primary"]) {
				return getBy($key, $id);
			}
		}
		
		throw new Exception("No Primary Key found");
	}


	public function getBy($field, $value) {
		$bdd = Bdd::getInstance();
		if (array_key_exists($field, $this->fields)) {
			return $this->simpleSelect(array($bdd->quoteIdent($field) . "=:value"), array("value"=>$value));
		}
		
		throw new Exception("Field $field not found");
	}

}
