<?php

class ModelData implements Iterator {

	private $model;
	private $values;
	private $isnew;
	private $isempty;
	private $statement;
	private $primary;
	private $foreign;


	function __construct($model, $values = NULL) {
		$this->model = $model;
		$this->foreign = array();
		$this->isempty = True;

		if (is_a($values, "PDOStatement")) {
			$this->statement = $values;
			$values = $this->statement->fetch();
		}

		$this->isnew = $values === NULL || $values === False;
		$this->values = array();
		foreach($this->model->getFields() as $field) {
			$this->_set($field, $field->getDefault());
			if ($field->isPrimary()) {
				$this->primary = $field;
			}
		}

		if (! $this->isnew) {
			$this->setValues($values);
		}
	}


	public function __toString() {
		return json_encode($this->getValues());
	}


	public function rewind() {
	}


	public function current() {
		return $this;
	}


	public function key() {
		return $this->values[$this->primary];
	}


	public function valid() {
		return !$this->isempty;
	}


	public function next() {
		if (!$this->statement) {
			$this->isempty = True;
			$this->isnew = True;
			foreach($this->model->getFields() as $field) {
				$this->values[$field->getName()] = $field->getDefault();
			}
			return $this;
		}
		$values = $this->statement->fetch();
		if ($values === False) {
			$this->isempty = True;
			$this->isnew = True;
			foreach($this->model->getFields() as $field) {
				$this->values[$field->getName()] = $field->getDefault();
			}
			return $this;
		}
		$this->isnew = False;
		$this->setValues($values);
		return $this;
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
			throw new Exception("Field ${field} not found in table " . $this->model->getTableName());

		return $this->values[$field];
	}


	private function _set($field, $value) {
		switch($field->getType()) {
			case ModelField::TYPE_BOOL:
				$value = intval($value);
				break;
		}

		$this->values[$field->getName()] = $value;
	}


	public function set($field, $value) {
		if (!array_key_exists($field, $this->values))
			throw new Exception("Field ${field} not found in table " . $this->model->getTableName());

		if (!is_a($field, "ModelField"))
			$field = $this->model->getField($field);

		$this->_set($field, $value);
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
		foreach($this->model->getFields() as $field) {
			if ($field->isAutoincrement()) {
				return $this->values[$field->getName()];
			}
		}
		return NULL;
	}


	public function save() {
		$bdd = Bdd::getInstance();
		if ($this->isnew) {
			$values = array();
			foreach($this->model->getFields() as $field) {
				if (! $field->isAutoincrement()) {
					$values[$field->getName()] = $this->values[$field->getName()];
				}
			}
			$id = $bdd->insert($this->model->getTableName(), $values);
			foreach($this->model->getFields() as $field) {
				if ($field->isAutoincrement()) {
					$this->values[$field->getName()] = $id;
					$this->model->dataChanged();
					break;
				}
			}
			$this->isnew = False;
		} else {
			foreach($this->model->getFields() as $field) {
				if ($field->isAutoincrement()) {
					$bdd->update($this->model->getTableName(), $this->values, $field->getName());
					$this->model->dataChanged();
					break;
				}
			}
		}
	}


	public function getForeign($field, $class) {
		if (! array_key_exists($field, $this->foreign)) {
			$obj = new $class();
			$this->foreign[$field] = $obj->getById($this->get($field));
		}

		return $this->foreign[$field];
	}

}
