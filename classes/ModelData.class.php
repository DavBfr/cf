<?php

class ModelData {

	private $model;
	private $values;
	private $isnew;
	private $isempty;
	private $statement;


	function __construct($model, $values = NULL) {
		$this->model = $model;
		$this->isempty = True;

		if (is_a($values, "PDOStatement")) {
			$this->statement = $values;
			$values = $this->statement->fetch();
		}

		$this->isnew = $values === NULL || $values === False;
		$this->values = array();
		foreach($this->model->getFields() as $key=>$val) {
			$this->values[$key] = $val["default"];
		}

		if (! $this->isnew) {
			$this->setValues($values);
		}
	}


	public function next() {
		$this->isempty;
		$values = $this->statement->fetch();
		if ($values === False) {
			$this->isempty = True;
			return False;
		}
		$this->setValues($values);
		return True;
	}


	public function setValues($values) {
		foreach($values as $key=>$val) {
			$this->set($key, $val);
		}
	}


	public function getValues() {
		return $this->values;
	}


	public function isNew() {
		return $this->isnew;
	}


	public function isEmpty() {
		return $this->isempty;
	}


	public function get($field) {
		if (!array_key_exists($field, $this->values))
			throw Exception("Field ${field} not found in table " . $this->model->getTableName());

		return $this->values[$field];
	}


	public function set($field, $value) {
		if (!array_key_exists($field, $this->values))
			throw Exception("Field ${field} not found in table " . $this->model->getTableName());

		$this->values[$field] = $value;
		$this->isempty = False;
	}


	public function has($field) {
		return array_key_exists($field, $this->values);
	}


	public function __get($field) {
		return $this->get($field);
	}


	public function __set($field, $value) {
		$this->set($field, $value);
	}


	public function __isset($field) {
		return $this->has($field);
	}


	public function getId() {
		foreach($this->model->getFields() as $key=>$val) {
			if ($val["autoincrement"]) {
				return $this->values[$key];
			}
		}
		return NULL;
	}


	public function save() {
		$bdd = Bdd::getInstance();
		if ($this->isnew) {
			$values = array();
			foreach($this->model->getFields() as $key=>$val) {
				if (! $val["autoincrement"]) {
					$values[$key] = $this->values[$key];
				}
			}
			$id = $bdd->insert($this->model->getTableName(), $values);
			foreach($this->model->getFields() as $key=>$val) {
				if ($val["autoincrement"]) {
					$this->values[$key] = $id;
					break;
				}
			}
			$this->isnew = False;
		} else {
			foreach($this->model->getFields() as $key=>$val) {
				if ($val["autoincrement"]) {
					$bdd->update($this->model->getTableName(), $this->values, $key);
					break;
				}
			}
		}
	}

}
