<?php

class ModelField {

	protected $table;
	protected $name;
	protected $props;


	function __construct($table, $name, $props = array()) {
		$this->table = $table;
		$this->name = $name;
		$this->props = array_merge($this->getDefaults(), $props);
	}


	public function getTableName() {
		return $this->table;
	}


	public function getName() {
		return $this->name;
	}


	public function getAttributes() {
		return $this->props;
	}


	public function getAttribute($name) {
		return $this->props[$name];
	}


	protected function getDefaults() {
		return array(
			"type"=>"int",
			"foreign"=>NULL,
			"display"=>$this->table.".".$this->name,
			"name"=>$this->table."_".$this->name,
			"caption"=>ucwords(str_replace("_", " ", $this->name)),
			"null"=>false,
			"edit"=>true,
			"default"=>NULL,
			"list"=>false,
			"primary"=>false,
			"autoincrement"=>false,
		);
	}


	public function __toString() {
		return $this->getCaption();
	}


	public function isBool() {
		return $this->props["type"] == "bool";
	}


	public function isText() {
		return $this->props["type"] == "text";
	}


	public function isPassword() {
		return $this->props["type"] == "password";
	}


	public function isEmail() {
		return $this->props["type"] == "email";
	}


	public function isUrl() {
		return $this->props["type"] == "url";
	}


	public function isInt() {
		return $this->props["type"] == "int";
	}


	public function isDate() {
		return $this->props["type"] == "date";
	}


	public function inList() {
		return $this->props["list"];
	}


	public function isEditable() {
		return $this->props["edit"];
	}


	public function getDefault() {
		return $this->props["default"];
	}


	public function getType() {
		return $this->props["type"];
	}


	public function getDbType() {
		switch ($this->props["type"]) {
			case "int":
			case "bool":
				return "INTEGER";
			case "text":
			case "password":
			case "email":
			case "url":
				return "TEXT";
			case "date":
				return "DATE";
			default:
				throw new Exception("Unable to find column type for " . $this->getName());
		}
	}


	public function hasNull() {
		return $this->props["null"];
	}


	public function getCaption() {
		return $this->props["caption"];
	}


	public function isAutoincrement() {
		return $this->props["autoincrement"];
	}


	public function isPrimary() {
		return $this->props["primary"];
	}


}
