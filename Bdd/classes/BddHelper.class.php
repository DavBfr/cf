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

use DateTime;
use Exception;
use Iterator;

abstract class BddHelper {

	protected function getParams() {
		return array();
	}


	/**
	 * @param string $string
	 * @return string
	 */
	abstract public function quote($string);


	/**
	 * @param string $field
	 * @return string
	 */
	abstract public function quoteIdent($field);


	/**
	 * @param string $table
	 * @param array $fields
	 * @return int
	 */
	abstract public function insert($table, array $fields);


	/**
	 * @param string $table
	 * @param array $fields
	 * @param string $key
	 * @return bool
	 */
	abstract public function update($table, array $fields, $key);


	/**
	 * @param string $table
	 * @param string $key
	 * @param string $value
	 * @return bool
	 */
	abstract public function delete($table, $key, $value);


	/**
	 * @param array $fields
	 * @param array $tables
	 * @param array $joint
	 * @param array $where
	 * @param string $filter
	 * @param array $filter_fields
	 * @param array $order
	 * @param array $group
	 * @param array $params
	 * @param int $limit
	 * @param int $pos
	 * @param bool $distinct
	 * @return string
	 */
	abstract public function getQueryString(array $fields, array $tables, array $joint, array $where, $filter, array $filter_fields, array $order, array $group, array $params, $limit, $pos, $distinct);


	/**
	 * @param array $fields
	 * @param array $tables
	 * @param array $joint
	 * @param array $where
	 * @param string $filter
	 * @param array $filter_fields
	 * @param array $order
	 * @param array $group
	 * @param array $params
	 * @param int $limit
	 * @param int $pos
	 * @param bool $distinct
	 * @return BddCursorHelper
	 */
	abstract public function getQueryValues(array $fields, array $tables, array $joint, array $where, $filter, array $filter_fields, array $order, array $group, array $params, $limit, $pos, $distinct);


	/**
	 * @param array $fields
	 * @param array $tables
	 * @param array $joint
	 * @param array $where
	 * @param string $filter
	 * @param array $filter_fields
	 * @param array $order
	 * @param array $group
	 * @param array $params
	 * @param int $limit
	 * @param int $pos
	 * @param bool $distinct
	 * @return array
	 */
	abstract public function getQueryValuesArray(array $fields, array $tables, array $joint, array $where, $filter, array $filter_fields, array $order, array $group, array $params, $limit, $pos, $distinct);


	/**
	 * @param array $tables
	 * @param array $joint
	 * @param array $where
	 * @param string $filter
	 * @param array $filter_fields
	 * @param array $group
	 * @param array $params
	 * @param bool $distinct
	 * @return int
	 */
	abstract public function getQueryCount(array $tables, array $joint, array $where, $filter, array $filter_fields, array $group, array $params, $distinct);


	/**
	 * @param string $name
	 * @return bool
	 */
	abstract public function tableExists($name);


	/**
	 * @param string $name
	 * @return string
	 */
	abstract public function dropTableQuery($name);


	/**
	 * @param string $name
	 * @return bool
	 */
	abstract public function dropTable($name);


	/**
	 * @param string $name
	 * @param array $table_structure
	 * @return string
	 */
	abstract public function createTableQuery($name, array $table_structure);


	/**
	 * @param string $name
	 * @param array $table_structure
	 * @return string[]
	 */
	abstract public function alterTableQuery($name, array $table_structure);


	/**
	 * @param string $name
	 * @param array $table_structure
	 */
	abstract public function createTable($name, array $table_structure);


	/**
	 * @param string $name
	 * @param array $table_structure
	 */
	abstract public function alterTable($name, array $table_structure);


	/**
	 * @return string[]
	 */
	abstract public function getTables();


	/**
	 * @param string $name
	 * @return array
	 */
	abstract public function getTableInfo($name);


	/**
	 * @param string $format
	 * @param string $date
	 * @return string
	 */
	public function strftime($format, $date) {
		return $date;
	}


	public function getBlob($value) {
		return $value;
	}


	public function setBlob($oldvalue, $newvalue) {
		return $newvalue;
	}


	/**
	 * @param string $name
	 * @return string
	 */
	public function updateTableName($name) {
		return $name;
	}


	/**
	 * @param string $name
	 * @param array $params
	 * @return array
	 */
	public function updateModelField($name, array $params) {
		if (!array_key_exists("type", $params) || $params["type"] == ModelField::TYPE_AUTO) {
			$params["type"] = ModelField::TYPE_INT;
		} elseif (array_key_exists("type", $params) && in_array($params["type"], array("password", "email", "url"))) {
			Cli::perr("Deprecated: Field type " . $params["type"] . " for $name");
			if (!array_key_exists("editor", $params)) {
				if ($params["type"] == "password")
					$params["editor"] = "passwd";
				else
					$params["editor"] = $params["type"];
			}
			$params["type"] = "text";
		}

		return array($name, $params);
	}


	/**
	 * @param string $type
	 * @param mixed $value
	 * @return mixed
	 */
	public function formatIn($type, $value) {
		if ($value === null)
			return $value;

		switch ($type) {
			case ModelField::TYPE_INT:
			case ModelField::TYPE_BOOL:
				$value = intval($value);
				break;
			case ModelField::TYPE_DATE:
				if ($value instanceof DateTime)
					$value = $value->format("Y-m-d");
				elseif (is_int($value) || is_double($value) || $value == strval(intval($value)) ||$value == strval(floatval($value)))
					$value = date("Y-m-d", intval($value));
				break;
			case ModelField::TYPE_TIME:
				if ($value instanceof DateTime)
					$value = $value->format("H:i:s");
				elseif (is_int($value) || is_double($value) || $value == strval(intval($value)) ||$value == strval(floatval($value)))
					$value = date("H:i:s", intval($value));
				break;
			case ModelField::TYPE_DATETIME:
				if ($value instanceof DateTime)
					$value = $value->format("Y-m-d H:i:s");
				elseif (is_int($value) || is_double($value) || $value == strval(intval($value)) ||$value == strval(floatval($value)))
					$value = date("Y-m-d H:i:s", intval($value));
				break;
		}
		return $value;
	}


	/**
	 * @param string $type
	 * @param mixed $value
	 * @return mixed
	 */
	public function formatOut($type, $value) {
		if ($value === null)
			return $value;

		switch ($type) {
			case ModelField::TYPE_INT:
				return intval($value);
			case ModelField::TYPE_BOOL:
				return intval($value) != 0;
			case ModelField::TYPE_DATETIME:
				if ($value instanceof DateTime) {
					return $value->getTimestamp();
				}
				try {
					$value = new DateTime($value);
					return $value->getTimestamp();
				} catch (Exception $e) {
					Logger::error("Date {$value} invalid: " . $e->getMessage());
					return $value;
				}
			case ModelField::TYPE_TIME:
				if ($value instanceof DateTime) {
					$value->setDate(1970, 1, 1);
					return $value->getTimestamp();
				}
				try {
					$value = new DateTime($value);
					$value->setDate(1970, 1, 1);
					return $value->getTimestamp();
				} catch (Exception $e) {
					Logger::error("Date {$value} invalid: " . $e->getMessage());
					return $value;
				}
			case ModelField::TYPE_DATE:
				$hours = date('H');
				$minutes = date('i');
				$seconds = date('s');
				if ($value instanceof DateTime) {
					$value->setTime($hours, $minutes, $seconds);
					return $value->getTimestamp();
				}
				try {
					$value = new DateTime($value);
					$value->setTime($hours, $minutes, $seconds);
					return $value->getTimestamp();
				} catch (Exception $e) {
					Logger::error("Date {$value} invalid: " . $e->getMessage());
					return $value;
				}
		}
		return $value;
	}
}


abstract class BddCursorHelper implements Iterator {
	protected $cursor = null;


	/**
	 * BddCursorHelper constructor.
	 * @param mixed $cursor
	 */
	public function __construct($cursor) {
		$this->cursor = $cursor;
	}


	/**
	 * @return array
	 */
	public function current() {
		$data = $this->cursor->current();
		return $data;
	}


	/**
	 * @return mixed
	 */
	public function key() {
		return $this->cursor->key();
	}


	public function next() {
		$this->cursor->next();
	}


	public function rewind() {
		$this->cursor->rewind();
	}


	/**
	 * @return bool
	 */
	public function valid() {
		return $this->cursor->valid();
	}

}
