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

class ModelField {
	const TYPE_AUTO = "auto";
	const TYPE_INT = "int";
	const TYPE_DECIMAL = "num";
	const TYPE_BOOL = "bool";
	const TYPE_TEXT = "text";
	const TYPE_PASSWD = "password";
	const TYPE_EMAIL = "email";
	const TYPE_URL = "url";
	const TYPE_DATE = "date"; // Y-m-d
	const TYPE_TIME = "time"; // h:i:s
	const TYPE_TIMESTAMP = "ts";
	const TYPE_BLOB = "blob";

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
			"type"=>self::TYPE_INT,
			"foreign"=>NULL, // (tablename, key, value)
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
		return $this->props["type"] == self::TYPE_TEXT;
	}


	public function isPassword() {
		return $this->props["type"] == self::TYPE_PASSWD;
	}


	public function isEmail() {
		return $this->props["type"] == self::TYPE_EMAIL;
	}


	public function isUrl() {
		return $this->props["type"] == self::TYPE_URL;
	}


	public function isInt() {
		return $this->props["type"] == self::TYPE_INT;
	}


	public function isDate() {
		return $this->props["type"] == self::TYPE_DATE;
	}


	public function isTime() {
		return $this->props["type"] == self::TYPE_TIME;
	}


	public function isDecimal() {
		return $this->props["type"] == self::TYPE_DECIMAL;
	}


	public function isTimestamp() {
		return $this->props["type"] == self::TYPE_TIMESTAMP;
	}


	public function isBlob() {
		return $this->props["type"] == self::TYPE_BLOB;
	}


	public function isSelect() {
		return $this->isForeign();
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

	public function getForeign() {
		return $this->props["foreign"];
	}


	public function isForeign() {
		return $this->props["foreign"] !== NULL;
	}


	public function valid($value) {
		switch ($this->getType()) {
			case self::TYPE_INT:
				return is_int($value) || preg_match('/^\d*$/', $value);
			case self::TYPE_TEXT:
			case self::TYPE_PASSWD:
			case self::TYPE_EMAIL:
			case self::TYPE_URL:
			case self::TYPE_DATE:
			case self::TYPE_TIME:
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
				throw new Exception("Unknown field type '".$this->getType()."'!");
		}
	}

}
