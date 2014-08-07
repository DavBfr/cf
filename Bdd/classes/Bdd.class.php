<?php

class Bdd {
	private static $instance = NULL;

	private $helper;
	private $driver;


	private function __construct() {
		$this->driver = substr(DBNAME, 0, strpos(DBNAME, ":"));
		$helper = ucFirst($this->driver)."Helper";
		if (class_exists($helper, true)) {
			$this->helper = new $helper(DBNAME, DBLOGIN, DBPASSWORD);
		} else {
			$this->helper = new PDOHelper(DBNAME, DBLOGIN, DBPASSWORD);
		}
	}


	public static function getInstance() {
		if (is_null(self::$instance)) {
			self::$instance = new self();
		}

		return self::$instance;
	}


	public function quote($string) {
		return $this->helper->quote($string);
	}


	public function quoteIdent($field) {
		return $this->helper->quoteIdent($field);
	}


	public function lastInsertId() {
		return $this->helper->lastInsertId();
	}


	public function insert($table, $fields) {
		return $this->helper->insert($table, $fields);
	}


	public function update($table, $fields, $key) {
		return $this->helper->update($table, $fields, $key);
	}


	public function delete($table, $key, $value) {
		return $this->helper->delete($table, $key, $value);
	}


	public function query($sql, $params = array()) {
		return $this->helper->query($sql, $params);
	}


	public function tableExists($name) {
		return $this->helper->tableExists($name);
	}


	public function dropTable($name) {
		return $this->helper->dropTable($name);
	}


	public function createTable($name, $table_structure) {
		return $this->helper->createTable($name, $table_structure);
	}


	public function getTables() {
		return $this->helper->getTables();
	}


	public function getTableInfo($name) {
		return $this->helper->getTableInfo($name);
	}

}
