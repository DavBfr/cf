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

		if (!is_null($values) && !is_array($values)) {
			$this->statement = $values;
			$this->statement->rewind();
			$values = $this->statement->current();
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
			foreach($values as $key=>$val) {
				$this->_set($key, $val);
			}
			$this->isempty = false;
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
		return !$this->isempty;
	}


	public function next() {
		if (!$this->statement) {
			$this->isempty = true;
			$this->isnew = true;
			foreach($this->model->getFields() as $field) {
				$this->_set($field->getName(), $field->getDefault());
			}
			return $this;
		}
		$this->statement->next();
		$values = $this->statement->current();
		if ($values === false) {
			$this->isempty = true;
			$this->isnew = true;
			foreach($this->model->getFields() as $field) {
				$this->_set($field->getName(), $field->getDefault());
			}
			return $this;
		}
		$this->isnew = false;
		foreach($values as $key=>$val) {
			$this->_set($key, $val);
		}
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

		$func = "get".ucfirst($field)."Field";
		if (is_callable(array($this, $func))) {
			return call_user_func(array($this, $func), $value);
		} elseif (is_callable(array($this->model, $func))) {
			return call_user_func(array($this->model, $func), $this, $value);
		}

		return $this->values[$field];
	}


	public function getBlob($field) {
		if (!array_key_exists($field, $this->values))
			throw new Exception("Field ${field} not found in table " . $this->model->getTableName());
		
		$bdd = Bdd::getInstance();
		return $bdd->getBlob($this->values[$field]);
	}


	public function setBlob($field, $value) {
		if (!array_key_exists($field, $this->values))
			throw new Exception("Field ${field} not found in table " . $this->model->getTableName());
		
		$bdd = Bdd::getInstance();
		$this->values[$field] = $bdd->setBlob($this->values[$field], $value);
	}


	private function _set($field, $value) {
		if (!is_a($field, "ModelField"))
			$field = $this->model->getField($field);
		
		if ($value != null) {
			switch($field->getType()) {
				case ModelField::TYPE_INT:
					$value = intval($value);
				case ModelField::TYPE_BOOL:
					$value = intval($value);
					break;
				case ModelField::TYPE_DATE:
					if ($value instanceof DateTime)
						$value = $value->format("Y-m-d");
					elseif (is_int($value))
						$value = date("Y-m-d", intval($value));
					break;
				case ModelField::TYPE_TIME:
				if ($value instanceof DateTime)
						$value = $value->format("h:i:s");
					elseif (is_int($value))
						$value = date("h:i:s", intval($value));
					break;
				case ModelField::TYPE_DATETIME:
				if ($value instanceof DateTime)
						$value = $value->format("Y-m-d h:i:s");
					elseif (is_int($value))
						$value = date("Y-m-d h:i:s", intval($value));
					break;
			}
		}
		
		$this->values[$field->getName()] = $value;
	}


	public function set($field, $value) {
		if (!array_key_exists($field, $this->values))
			throw new Exception("Field ${field} not found in table " . $this->model->getTableName());

		if (!is_a($field, "ModelField"))
			$field = $this->model->getField($field);

		$func = "set".ucfirst($field->getName())."Field";
		if (is_callable(array($this, $func))) {
			$value = call_user_func(array($this, $func), $value);
		} elseif (is_callable(array($this->model, $func))) {
			$value = call_user_func(array($this->model, $func), $this, $value);
		}

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
			$bdd->update($this->model->getTableName(), $this->values, $this->model->getPrimaryField());
			$this->model->dataChanged();
		}
	}


	public function delete() {
		$bdd = Bdd::getInstance();
		if (!$this->isnew) {
			$pf = $this->model->getPrimaryField();
			$pv = $this->get($pf);
			$bdd->delete($this->model->getTableName(), $pf, $pv);
			$this->isnew = true;
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
