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

class Bdd {
	private static $instance = NULL;

	private $helper;
	private $driver;


	private function __construct() {
		$this->driver = substr(DBNAME, 0, strpos(DBNAME, ":"));
		$helper = ucFirst($this->driver)."Helper";
		if (class_exists($helper, true)) {
			$this->helper = new $helper(DBNAME, DBLOGIN, DBPASSWORD);
		} else {
			$this->helper = new PDOHelper(DBNAME, DBLOGIN, DBPASSWORD);
		}
	}


	public static function getInstance() {
		if (is_null(self::$instance)) {
			self::$instance = new self();
		}

		return self::$instance;
	}


	public function quote($string) {
		return $this->helper->quote($string);
	}


	public function quoteIdent($field) {
		return $this->helper->quoteIdent($field);
	}


	public function lastInsertId() {
		return $this->helper->lastInsertId();
	}


	public function insert($table, $fields) {
		return $this->helper->insert($table, $fields);
	}


	public function update($table, $fields, $key) {
		return $this->helper->update($table, $fields, $key);
	}


	public function delete($table, $key, $value) {
		return $this->helper->delete($table, $key, $value);
	}


	public function query($sql, $params = array()) {
		return $this->helper->query($sql, $params);
	}


	public function tableExists($name) {
		return $this->helper->tableExists($name);
	}


	public function dropTable($name) {
		return $this->helper->dropTable($name);
	}


	public function createTable($name, $table_structure) {
		return $this->helper->createTable($name, $table_structure);
	}


	public function getTables() {
		return $this->helper->getTables();
	}


	public function getTableInfo($name) {
		return $this->helper->getTableInfo($name);
	}

}
