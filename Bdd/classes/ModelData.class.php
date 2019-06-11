<?php namespace DavBfr\CF;

/**
 * Copyright (C) 2013-2018 David PHAM-VAN
 *
 * This library is free software; you can redistribute it and/or
 * modify it under the terms of the GNU Lesser General Public
 * License as published by the Free Software Foundation; either
 * version 2.1 of the License, or (at your option) any later version.
 *
 * This library is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the GNU
 * Lesser General Public License for more details.
 *
 * You should have received a copy of the GNU Lesser General Public
 * License along with this library; if not, write to the Free Software
 * Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301, USA.
 **/

use Exception;
use Iterator;
use ArrayAccess;

class ModelData implements Iterator, ArrayAccess {

	/** @var Model $model */
	private $model;
	private $values;
	private $isnew;
	private $isempty;
	private $statement;
	/** @var string $primary */
	private $primary;
	private $foreign;


	/**
	 * ModelData constructor.
	 * @param Model $model
	 * @param BddCursorHelper|null $values
	 * @throws Exception
	 */
	public function __construct(Model $model, BddCursorHelper $values = null) {
		$this->model = $model;
		$this->foreign = array();
		$this->isempty = true;

		if (!is_null($values) && !is_array($values)) {
			$this->statement = $values;
			$this->statement->rewind();
			$values = $this->statement->current();
		}

		$this->isnew = $values === null || $values === false;
		$this->values = array();
		/** @var ModelField $field */
		foreach ($this->model->getFields() as $field) {
			$this->_set($field, $field->getDefault());
			if ($field->isPrimary()) {
				$this->primary = $field;
			}
		}

		if (!$this->isnew) {
			foreach ($values as $key => $val) {
				$this->_set($key, $val);
			}
			$this->isempty = false;
		}
	}


	/**
	 * @return string
	 * @throws Exception
	 */
	public function __toString() {
		return json_encode($this->getValues());
	}


	/**
	 * @return Model
	 */
	public function getModel() {
		return $this->model;
	}


	public function rewind() {
	}


	/**
	 * @return $this
	 */
	public function current() {
		return $this;
	}


	/**
	 * @return mixed
	 */
	public function key() {
		return $this->values[$this->primary];
	}


	/**
	 * @return bool
	 */
	public function valid() {
		return !$this->isempty;
	}


	/**
	 * @return $this
	 * @throws Exception
	 */
	public function next() {
		if (!$this->statement) {
			$this->isempty = true;
			$this->isnew = true;
			/** @var ModelField $field */
			foreach ($this->model->getFields() as $field) {
				$this->_set($field->getName(), $field->getDefault());
			}
			return $this;
		}
		$this->statement->next();
		$values = $this->statement->current();
		if ($values === false) {
			$this->isempty = true;
			$this->isnew = true;
			/** @var ModelField $field */
			foreach ($this->model->getFields() as $field) {
				$this->_set($field->getName(), $field->getDefault());
			}
			return $this;
		}
		$this->isnew = false;
		foreach ($values as $key => $val) {
			$this->_set($key, $val);
		}
		return $this;
	}


	/**
	 * @param array $values
	 * @throws Exception
	 */
	public function setValues(array $values) {
		foreach ($values as $key => $val) {
			$this->set($key, $val);
		}
	}


	/**
	 * @return array
	 * @throws Exception
	 */
	public function getValues() {
		$values = array();
		foreach ($this->values as $key => $val) {
			$values[$key] = $this->get($key);
		}
		return $values;
	}


	/**
	 * @return bool
	 */
	public function isNew() {
		return $this->isnew;
	}


	/**
	 * @return bool
	 */
	public function isEmpty() {
		return $this->isempty;
	}

	/**
	 * @param string $field
	 * @return mixed
	 * @throws Exception
	 */
	public function raw($field) {
		return $this->values[$field];
	}


	/**
	 * @param string $field
	 * @return mixed
	 * @throws Exception
	 */
	private function _get($field) {
		if (!is_a($field, __NAMESPACE__ . "\\ModelField"))
			$field = $this->model->getField($field);

		return $field->formatOut($this->values[$field->getName()]);
	}


	/**
	 * @param string $field
	 * @return mixed
	 * @throws Exception
	 */
	public function get($field) {
		if (!array_key_exists($field, $this->values))
			throw new Exception("Field ${field} not found in table " . $this->model->getTableName());

		$value = $this->_get($field);

		$func = "get" . ucfirst($field) . "Field";
		if (is_callable(array($this, $func))) {
			return call_user_func(array($this, $func), $value);
		} elseif (is_callable(array($this->model, $func))) {
			return call_user_func(array($this->model, $func), $this, $value);
		}

		return $value;
	}


	/**
	 * @param string $field
	 * @return mixed
	 * @throws Exception
	 */
	public function getBlob($field) {
		if (!array_key_exists($field, $this->values))
			throw new Exception("Field ${field} not found in table " . $this->model->getTableName());

		$bdd = Bdd::getInstance();
		return $bdd->getBlob($this->values[$field]);
	}


	/**
	 * @param string $field
	 * @param mixed $value
	 * @throws Exception
	 */
	public function setBlob($field, $value) {
		if (!array_key_exists($field, $this->values))
			throw new Exception("Field ${field} not found in table " . $this->model->getTableName());

		$bdd = Bdd::getInstance();
		$this->values[$field] = $bdd->setBlob($this->values[$field], $value);
	}


	/**
	 * @param string $field
	 * @param mixed $value
	 * @throws Exception
	 */
	private function _set($field, $value) {
		if (!is_a($field, __NAMESPACE__ . "\\ModelField"))
			$field = $this->model->getField($field);

		$this->values[$field->getName()] = $field->format($value);
	}


	/**
	 * @param string $field
	 * @param mixed $value
	 * @throws Exception
	 */
	public function set($field, $value) {
		if (!array_key_exists($field, $this->values))
			throw new Exception("Field ${field} not found in table " . $this->model->getTableName());

		if (!is_a($field, __NAMESPACE__ . "\\ModelField"))
			$field = $this->model->getField($field);

		$func = "set" . ucfirst($field->getName()) . "Field";
		if (is_callable(array($this, $func))) {
			$value = call_user_func(array($this, $func), $value);
		} elseif (is_callable(array($this->model, $func))) {
			$value = call_user_func(array($this->model, $func), $this, $value);
		}

		$this->_set($field, $value);
		$this->isempty = false;
	}


	/**
	 * @param string $field
	 * @return bool
	 */
	public function has($field) {
		return array_key_exists($field, $this->values);
	}


	/**
	 * @param string $field
	 * @return mixed
	 * @throws Exception
	 */
	public function __get($field) {
		return $this->get($field);
	}


	/**
	 * @param string $field
	 * @param mixed $value
	 * @throws Exception
	 */
	public function __set($field, $value) {
		$this->set($field, $value);
	}


	/**
	 * @param string $field
	 * @return bool
	 */
	public function __isset($field) {
		return $this->has($field);
	}


	/**
	 * Whether a offset exists
	 * @param string $offset
	 * @return boolean true on success or false on failure.
	 */
	public function offsetExists($offset) {
		return $this->has($offset);
	}


	/**
	 * Offset to retrieve
	 * @param string $offset
	 * @return mixed
	 * @throws Exception
	 */
	public function offsetGet($offset) {
		return $this->get($offset);
	}


	/**
	 * Offset to set
	 * @param string $offset
	 * @param mixed $value
	 * @return void
	 * @throws Exception
	 */
	public function offsetSet($offset, $value) {
		$this->set($offset, $value);
	}


	/**
	 * Offset to unset
	 * @param string $offset
	 * @return void
	 * @throws Exception
	 */
	public function offsetUnset($offset) {
		$this->set($offset, null);
	}


	/**
	 * @return mixed|null
	 */
	public function getId() {
		/** @var ModelField $field */
		foreach ($this->model->getFields() as $field) {
			if ($field->isAutoincrement()) {
				return $this->values[$field->getName()];
			}
		}
		return null;
	}


	/**
	 * @throws Exception
	 */
	public function save() {
		//$this->valid();

		$bdd = Bdd::getInstance();
		if ($this->isnew) {
			$values = array();
			/** @var ModelField $field */
			foreach ($this->model->getFields() as $field) {
				if (!$field->isAutoincrement()) {
					$values[$field->getName()] = $this->values[$field->getName()];
				}
			}
			$id = $bdd->insert($this->model->getTableName(), $values);
			/** @var ModelField $field */
			foreach ($this->model->getFields() as $field) {
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


	/**
	 * @throws Exception
	 */
	public function delete() {
		$bdd = Bdd::getInstance();
		if (!$this->isnew) {
			$pf = $this->model->getPrimaryField();
			$pv = $this->get($pf);
			$bdd->delete($this->model->getTableName(), $pf, $pv);
			$this->isnew = true;
		}
	}


	/**
	 * @param string $field
	 * @param Model|null $class
	 * @return mixed
	 * @throws Exception
	 */
	public function getForeign($field, Model $class = null) {
		if (!array_key_exists($field, $this->foreign)) {
			if ($class === null)
				/** @var Model $obj */
				$obj = $this->model->getForeign($field);
			else
				$obj = new $class();

			$this->foreign[$field] = $obj->getById($this->get($field));
		}

		return $this->foreign[$field];
	}

}
