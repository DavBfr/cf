<?php

abstract class Crud extends Rest {

	protected $table;
	protected $fields;
	protected $bdd;


	function __construct() {
		parent::__construct();
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


	public function getRoutes() {
		$this->addRoute("/", "GET", "output_list");
		$this->addRoute("/count", "GET", "output_count");
		$this->addRoute("/edit", "GET", "output_form");
		$this->addRoute("/", "DELETE", "delete_form");
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


	public function output_list($filtres = array()) {
		list($fields, $tables, $where, $limit) = $this->get_list($filtres);
		$reponse = $this->bdd->query($this->build_query($fields, $tables, $where, $limit));
		$collection = array();
		foreach ($reponse as $row) {
			$collection[] = $row;
		}
		output_json(array(
			'success'=>1,
			'collection'=>$collection
		));
	}


	public function output_count($filtres = array()) {
		list($fields, $tables, $where, $limit) = $this->get_list($filtres);
		$reponse = $this->bdd->query($this->build_query(array("COUNT(*)"), $tables, $where));
		$count = $reponse->fetch(PDO::FETCH_NUM);
		if ($count)
			output_json(array(
				'success'=>1,
				'count'=>intVal($count[0])
			));
		else {
			output_json(array(
				'success'=>0
			));

		}
	}


	public function output_form($filtres = array()) {
		$id = $filtres["id"];

		$fields = array();
		$tables = array($this->table);
		$where = array("1");
		foreach($this->fields as $name => $prop) {
			if ($prop["edit"] || $prop["primary"]) {
				$fields[] = $this->table.".".$name . " as " . $prop["name"];
			}
			if ($prop["primary"]) {
				$where[] = $this->table.".".$name . "=:id";
			}
		}
		$query = $this->build_query($fields, $tables, $where);
		$reponse = $this->bdd->query($query, array(
			"id" => $id
		));
		output_json(array(
			'success'=>1,
			'collection'=>$reponse->fetch()
		));
	}

	public function delete_form($filtres = array()) {
		$id = $filtres["id"];

		foreach($this->fields as $name => $prop) {
			if ($prop["primary"]) {
				$this->bdd->query("DELETE FROM `" . $this->table . "` WHERE " . $name . "=:id", array(
					"id" => $id
				));
				output_json(array('success'=>1));
			}
		}
		
		output_json(array('success'=>0, 'error'=>'Pas de clé primaire'));
	}
	
	public function get_detail_template($prefix) {
		$ret = '<form class="form-horizontal" role="form">'."\n";
		foreach($this->fields as $name => $prop) {
			if ($prop["caption"] && $prop["edit"]) {
				$ret .= "\n\t".'<div class="form-group">'."\n";
				$ret .= "\t\t".'<label class="col-sm-2 control-label">'. $prop['caption'] . '</label>'."\n";
				$ret .= "\t\t".'<div class="col-sm-10">'."\n";
					$ret .= "\t\t\t".'<p class="form-control-static">{{' . $prefix . $prop['name'] . '}}</p>'."\n";
				$ret .= "\t\t".'</div>'."\n";
				$ret .= "\t".'</div>'."\n";
			}
		}
		$ret .= "</form>"."\n";
		return $ret;
	}

}
