<?php
/**
 * Copyright (C) 2013-2015 David PHAM-VAN
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

/**
 * https://github.com/etconsilium/pdo-mongodb/blob/master/pdo/mongodb.php
 * http://php.net/mongo
 * http://blog.mongodb.org/post/24960636131/mongodb-for-the-php-mind-part-1
 * http://www.querymongo.com
 **/

abstract class BddHelper {
	
	protected function getParams() {
		return array();
	}
	
	abstract public function quote($string);

	abstract public function quoteIdent($field);

	abstract public function insert($table, $fields);

	abstract public function update($table, $fields, $key);

	abstract public function delete($table, $key, $value);

	abstract public function getQueryString($fields, $tables, $joint, $where, $filter, $order, $group, $params, $limit, $pos, $distinct);

	abstract public function getQueryValues($fields, $tables, $joint, $where, $filter, $order, $group, $params, $limit, $pos, $distinct);

	abstract public function getQueryValuesArray($fields, $tables, $joint, $where, $filter, $order, $group, $params, $limit, $pos, $distinct);

	abstract public function getQueryCount($tables, $joint, $where, $filter, $group, $params, $distinct);

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
	protected $cursor;

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
