<?php
/**
 * Copyright (C) 2013-2014 David PHAM-VAN
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License
 * as published by the Free Software Foundation; version 2
 * of the License.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301, USA.
 **/

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
		$this->isempty = true;

		if (is_a($values, "PDOStatement")) {
			$this->statement = $values;
			$values = $this->statement->fetch();
		}

		$this->isnew = $values === NULL || $values === false;
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


	public function getModel() {
		return $this->model;
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
		if ($this->isempty)
			throw new Exception("Empty data");
		
		foreach($this->model->getFields() as $field) {
			if (!$field->valid($this->get($field->getName()))) {
				throw new Exception("Invalid data for ".$field->getName());
			}
		}
	}


	public function next() {
		if (!$this->statement) {
			$this->isempty = true;
			$this->isnew = true;
			foreach($this->model->getFields() as $field) {
				$this->values[$field->getName()] = $field->getDefault();
			}
			return $this;
		}
		$values = $this->statement->fetch();
		if ($values === false) {
			$this->isempty = true;
			$this->isnew = true;
			foreach($this->model->getFields() as $field) {
				$this->values[$field->getName()] = $field->getDefault();
			}
			return $this;
		}
		$this->isnew = false;
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
			case ModelField::TYPE_DATE:
				if (is_int($value))
					$value = date("Y-m-d", intval($value));
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
		$this->isempty = false;
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
		//$this->valid();
		
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
			$this->isnew = false;
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


	public function getForeign($field, $class = NULL) {
		if (! array_key_exists($field, $this->foreign)) {
			if ($class === NULL)
				$obj = $this->model->getForeign($field);
			else
				$obj = new $class();
			
			$this->foreign[$field] = $obj->getById($this->get($field));
		}

		return $this->foreign[$field];
	}

}
