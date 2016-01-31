<?php namespace DavBfr\CF;
/**
 * Copyright (C) 2013-2015 David PHAM-VAN
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

use Iterator;

abstract class BddHelper {

	protected function getParams() {
		return array();
	}

	abstract public function quote($string);

	abstract public function quoteIdent($field);

	abstract public function insert($table, $fields);

	abstract public function update($table, $fields, $key);

	abstract public function delete($table, $key, $value);

	abstract public function getQueryString($fields, $tables, $joint, $where, $filter, $filter_fields, $order, $group, $params, $limit, $pos, $distinct);

	abstract public function getQueryValues($fields, $tables, $joint, $where, $filter, $filter_fields, $order, $group, $params, $limit, $pos, $distinct);

	abstract public function getQueryValuesArray($fields, $tables, $joint, $where, $filter, $filter_fields, $order, $group, $params, $limit, $pos, $distinct);

	abstract public function getQueryCount($tables, $joint, $where, $filter, $filter_fields, $group, $params, $distinct);

	abstract public function tableExists($name);

	abstract public function dropTableQuery($name);

	abstract public function dropTable($name);

	abstract public function createTableQuery($name, $table_structure);

	abstract public function createTable($name, $table_structure);

	abstract public function getTables();

	abstract public function getTableInfo($name);

	public function strftime($format, $date) {
		return $date;
	}

	public function getBlob($value) {
		return $value;
	}


	public function setBlob($oldvalue, $newvalue) {
		return $newvalue;
	}


	public function updateTableName($name) {
		return $name;
	}


	public function updateModelField($name, $params) {
		if (!array_key_exists("type", $params) || $params["type"] == ModelField::TYPE_AUTO) {
			$params["type"] = ModelField::TYPE_INT;
		}
		return array($name, $params);
	}


	public function collection() {
		return new Collection($this);
	}

}


abstract class BddCursorHelper implements Iterator {
	protected $cursor = null;

	public function __construct($cursor) {
		$this->cursor = $cursor;
	}


 	public function current() {
		$data = $this->cursor->current();
		return $data;
	}


	public function key() {
		return $this->cursor->key();
	}


 	public function next() {
		$this->cursor->next();
	}


	public function rewind() {
		$this->cursor->rewind();
	}


	public function valid() {
		return $this->cursor->valid();
	}

}


class BddBlobHelper {

}
