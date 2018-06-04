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

class ModelField {
	const TYPE_AUTO = "auto";
	const TYPE_INT = "int";
	const TYPE_DECIMAL = "num";
	const TYPE_BOOL = "bool";
	const TYPE_TEXT = "text";
	const TYPE_DATE = "date"; // Y-m-d
	const TYPE_TIME = "time"; // h:i:s
	const TYPE_DATETIME = "datetime"; // Y-m-d h:i:s
	const TYPE_TIMESTAMP = "ts";
	const TYPE_BLOB = "blob";

	protected $table;
	protected $name;
	protected $props;


	/**
	 * ModelField constructor.
	 * @param string $table
	 * @param string $name
	 * @param array $props
	 */
	public function __construct($table, $name, array $props = array()) {
		$this->table = $table;
		$this->name = $name;
		$this->props = array_merge($this->getDefaults(), $props);
	}


	/**
	 * @return string
	 */
	public function getTableName() {
		return $this->table;
	}


	/**
	 * @return string
	 */
	public function getName() {
		return $this->name;
	}


	/**
	 * @return string
	 */
	public function getFullName() {
		return $this->table . "." . $this->name;
	}


	/**
	 * @return array
	 */
	public function getAttributes() {
		return $this->props;
	}


	/**
	 * @param $name
	 * @return mixed
	 */
	public function getAttribute($name) {
		return $this->props[$name];
	}


	/**
	 * @return array
	 */
	protected function getDefaults() {
		return array(
			"type" => self::TYPE_INT,
			"foreign" => null, // (tablename, key, value)
			"display" => $this->table . "." . $this->name,
			"name" => $this->table . "_" . $this->name,
			"caption" => ucwords(str_replace("_", " ", $this->name)),
			"null" => false,
			"edit" => true,
			"default" => null,
			"list" => false,
			"primary" => false,
			"autoincrement" => false,
			"editor" => null
		);
	}


	/**
	 * @return string
	 */
	public function __toString() {
		return (string)$this->getCaption();
	}


	/**
	 * @return string
	 */
	public function getEditor() {
		if ($this->props["editor"] === null) {
			if ($this->isAutoincrement())
				$this->props["editor"] = "auto";
			elseif ($this->isSelect())
				$this->props["editor"] = "select";
			elseif ($this->isBool())
				$this->props["editor"] = "bool";
			elseif ($this->isInt())
				$this->props["editor"] = "int";
			elseif ($this->isDecimal())
				$this->props["editor"] = "num";
			elseif ($this->isDate())
				$this->props["editor"] = "date";
			elseif ($this->isTime())
				$this->props["editor"] = "time";
			elseif ($this->isDateTime() || $this->isTimestamp())
				$this->props["editor"] = "date-time";
		}

		if ($this->props["editor"] == 'password')
			return 'passwd';

		return $this->props["editor"];
	}


	/**
	 * @return bool
	 */
	public function isBool() {
		return $this->props["type"] == "bool";
	}


	/**
	 * @return bool
	 */
	public function isText() {
		return $this->props["type"] == self::TYPE_TEXT;
	}


	/**
	 * @return bool
	 */
	public function isPassword() {
		trigger_error('Deprecated: isPassword() is deprecated', E_NOTICE);
		return $this->props["editor"] == "passwd";
	}


	/**
	 * @return bool
	 */
	public function isEmail() {
		trigger_error('Deprecated: isEmail() is deprecated', E_NOTICE);
		return $this->props["editor"] == "email";
	}


	/**
	 * @return bool
	 */
	public function isUrl() {
		trigger_error('Deprecated: isUrl() is deprecated', E_NOTICE);
		return $this->props["editor"] == "url";
	}


	/**
	 * @return bool
	 */
	public function isInt() {
		return $this->props["type"] == self::TYPE_INT;
	}


	/**
	 * @return bool
	 */
	public function isDate() {
		return $this->props["type"] == self::TYPE_DATE;
	}


	/**
	 * @return bool
	 */
	public function isTime() {
		return $this->props["type"] == self::TYPE_TIME;
	}


	/**
	 * @return bool
	 */
	public function isDateTime() {
		return $this->props["type"] == self::TYPE_DATETIME;
	}


	/**
	 * @return bool
	 */
	public function isDecimal() {
		return $this->props["type"] == self::TYPE_DECIMAL;
	}


	/**
	 * @return bool
	 */
	public function isTimestamp() {
		return $this->props["type"] == self::TYPE_TIMESTAMP;
	}


	/**
	 * @return bool
	 */
	public function isBlob() {
		return $this->props["type"] == self::TYPE_BLOB;
	}


	/**
	 * @return bool
	 */
	public function isSelect() {
		return $this->isForeign();
	}


	/**
	 * @return bool
	 */
	public function inList() {
		return $this->props["list"];
	}


	/**
	 * @param bool $value
	 */
	public function setInList($value) {
		$this->props["list"] = (boolean)$value;
	}


	/**
	 * @return bool
	 */
	public function isEditable() {
		return $this->props["edit"];
	}


	/**
	 * @param bool $value
	 */
	public function setEditable($value) {
		$this->props["edit"] = (bool)$value;
	}


	/**
	 * @return mixed
	 */
	public function getDefault() {
		return $this->props["default"];
	}


	/**
	 * @return string
	 */
	public function getType() {
		return $this->props["type"];
	}


	/**
	 * @return bool
	 */
	public function hasNull() {
		return (bool)$this->props["null"];
	}


	/**
	 * @return string
	 */
	public function getCaption() {
		return $this->props["caption"];
	}


	/**
	 * @return bool
	 */
	public function isAutoincrement() {
		return $this->props["autoincrement"];
	}


	/**
	 * @return bool
	 */
	public function isPrimary() {
		return $this->props["primary"];
	}


	/**
	 * @return string[]|null
	 */
	public function getForeign() {
		return $this->props["foreign"];
	}


	/**
	 * @return bool
	 */
	public function isForeign() {
		return $this->props["foreign"] !== null;
	}


	/**
	 * @return string
	 */
	public function getForeignTable() {
		return "foreign_" . $this->name;
	}


	/**
	 * @param mixed $value
	 * @return bool
	 * @throws Exception
	 */
	public function valid($value) {
		if ($this->hasNull() && $value === null) {
			return true;
		}
		switch ($this->getType()) {
			case self::TYPE_INT:
				return is_int($value) || preg_match('/^\d*$/', $value);
			case self::TYPE_TEXT:
			case self::TYPE_DATE:
			case self::TYPE_TIME:
			case self::TYPE_DATETIME:
				return is_string($value);
			case self::TYPE_BOOL:
				return $value === true || $value === false;
			case self::TYPE_DECIMAL:
				return is_numeric($value) || preg_match('/^(.\d)*$/', $value);
			case self::TYPE_TIMESTAMP:
				return preg_match('/^\d\d\d\d-(\d)?\d-(\d)?\d \d\d:\d\d:\d\d$/', $value);
			case self::TYPE_BLOB:
				return true;
			default:
				throw new Exception("Unknown field type '" . $this->getType() . "'!");
		}
	}


	/**
	 * @param mixed $value
	 * @return mixed
	 * @throws Exception
	 */
	public function format($value) {
		$bdd = Bdd::getInstance();
		return $bdd->formatIn($this->getType(), $value);
	}


	/**
	 * @param mixed $value
	 * @return mixed
	 * @throws Exception
	 */
	public function formatOut($value) {
		$bdd = Bdd::getInstance();
		return $bdd->formatOut($this->getType(), $value);
	}

}
