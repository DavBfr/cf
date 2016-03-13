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

use PDO;
use PDOException;
use Exception;

class PDOHelper extends BddHelper {
	protected $pdo;

	public function __construct($dsn, $login, $password) {
		try {
			$this->pdo = new PDO($dsn, $login, $password, $this->getParams());
			$this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
		} catch (PDOException $e) {
			ErrorHandler::error(500, NULL, "Unable to connect to Database: " . $e->getMessage());
		} catch (Exeption $e) {
			ErrorHandler::error(500, NULL, "Unable to connect to Database: " . $e->getMessage());
		}
	}


	public function quote($string) {
		return $this->pdo->quote($string);
	}


	public function quoteIdent($field) {
		return "`".str_replace("`","``",$field)."`";
	}


	public function insert($table, $fields) {
		$this->query("INSERT INTO " .
			$table . "(" . implode(", ", array_keys($fields)) .
			") VALUES (:" . implode(", :", array_keys($fields)) . ")", $fields);
		return $this->pdo->lastInsertId();
	}


	public function update($table, $fields, $key) {
		$s = array();
		foreach($fields as $k=>$v) {
			if ($key != $k)
				$s[] = "$k = :$k";
		}
		$this->query("UPDATE " .
			$table . " SET " . implode(", ", $s) ." WHERE $key = :$key", $fields);
		return true;
	}


	public function delete($table, $key, $value) {
		$this->query("DELETE FROM " . $table . " WHERE $key = :key", array("key" => $value));
		return true;
	}


	public function query($sql, $params = array()) {
		Logger::Debug("Query ${sql} ", $params);
		$reponse = $this->pdo->prepare($sql);
		if ($reponse === false) {
			$error = $this->pdo->errorInfo();
			ErrorHandler::error(500, NULL, "Error in SQL statement ${error[0]} (${error[1]}) ${error[2]} in\n$sql");
		}
		$reponse->setFetchMode(PDO::FETCH_NAMED);
		$result = $reponse->execute($params);
		if ($result === false) {
			$error = $reponse->errorInfo();
			ErrorHandler::error(500, NULL, "Sql error ${error[0]} (${error[1]}) ${error[2]} in\n$sql");
		}
		return $reponse;
	}


	public function tableExists($name) {
		try {
			$ret = $this->pdo->query('SELECT 1 FROM '.$name);
			return $ret !== false;
		} catch (PDOException $e) {
			return false;
		}
	}


	protected function buildTableColumns($table_structure) {
		return Array();
	}


	public function dropTableQuery($name) {
		return "DROP TABLE IF EXISTS ".$this->quoteIdent($name);
	}


	public function dropTable($name) {
		$this->query($this->dropTableQuery($name));
	}


	public function createTableQuery($name, $table_structure) {
		$columns = $this->buildTableColumns($table_structure);
		$query  = "CREATE TABLE IF NOT EXISTS ".$this->quoteIdent($name)." (\n  ";
		$cols = array();
		foreach ($columns as $column_name => $column_type) {
			$cols[] = $this->quoteIdent($column_name).' '.$column_type;
		}
		$query .= implode(",\n  ", $cols);
		$query .= "\n)";
		return $query;
	}


	public function createTable($name, $table_structure) {
		$this->query($this->createTableQuery($name, $table_structure));
	}


	public function getTables() {
		return NULL;
	}


	public function getTableInfo($name) {
		return NULL;
	}


	public function getQueryString($fields, $tables, $joint, $where, $filter, $filter_fields, $order, $group, $params, $limit, $pos, $distinct) {
		$query = "SELECT ".($distinct ? "DISTINCT ":"");

		if (count($fields) == 0)
			$query .= "*";
		else {
			$_fields = array();
			foreach($fields as $k=>$v) {
				if (is_int($k))
					$_fields[] = $v;
				else
					$_fields[] = "$v as $k";
			}
			$query .= implode(", ", $_fields);

		}

		$query .= " FROM ".implode(", ", $tables);

		if (count($joint) > 0) {
			$joints = array();

			foreach($joint as $k=>$v) {
				$joints[] = "LEFT JOIN ${v[0]} ON ${v[1]}";
			}
			$query .= " ".implode(" ", $joints);
		}

		if ($filter) {
			$value = $this->quote("%".$filter."%");

			$filter = array();
			if ($filter_fields == NULL) {
				$filter_fields = $fields;
			}
			foreach ($filter_fields as $field) {
				$filter[] = $field . " LIKE " . $value;
			}
			if (count($filter) > 0) {
				$where[] = implode(" OR ", $filter);
			}
		}

		if (count($where) > 0)
			$query .= " WHERE (".implode(") AND (", $where).")";

		if (count($group) > 0)
			$query .= " GROUP BY ".implode(", ", $group);

		if (count($order) > 0)
			$query .= ' ORDER BY '. implode(", ", $order);

		if ($limit)
			$query .= ' LIMIT ' . ($pos * $limit) .", " . $limit;

		return $query;
	}


	public function getQueryValues($fields, $tables, $joint, $where, $filter, $filter_fields, $order, $group, $params, $limit, $pos, $distinct) {
		$sql = $this->getQueryString($fields, $tables, $joint, $where, $filter, $filter_fields, $order, $group, $params, $limit, $pos, $distinct);
		return new PDOStatementHelper($this, $this->query($sql, $params));
	}


	public function getQueryValuesArray($fields, $tables, $joint, $where, $filter, $filter_fields, $order, $group, $params, $limit, $pos, $distinct) {
		$collection = array();
		$result = $this->getQueryValues($fields, $tables, $joint, $where, $filter, $filter_fields, $order, $group, $params, $limit, $pos, $distinct);
		foreach ($result as $row) {
			$collection[] = $row;
		}
		return $collection;
	}


	public function getQueryCount($tables, $joint, $where, $filter, $filter_fields, $group, $params, $distinct) {
		$sql = $this->getQueryString(array("COUNT(*)"), $tables, $joint, $where, $filter, $filter_fields, array(), $group, $params, NULL, 0, $distinct);
		$values = $this->query($sql, $params);
		$count = $values->fetch(PDO::FETCH_NUM);
		return intVal($count[0]);
	}


	protected function getDbType($type) {
		switch ($type) {
			case ModelField::TYPE_INT:
			case ModelField::TYPE_TIMESTAMP:
			case ModelField::TYPE_BOOL:
				return "INTEGER";
			case ModelField::TYPE_DECIMAL:
				return "NUMBER";
				case ModelField::TYPE_TEXT:
			case ModelField::TYPE_PASSWD:
			case ModelField::TYPE_EMAIL:
			case ModelField::TYPE_URL:
				return "TEXT";
			case ModelField::TYPE_DATE:
				return "DATE";
			case ModelField::TYPE_TIME:
				return "TIME";
			case ModelField::TYPE_DATETIME:
					return "DATETIME";
			case ModelField::TYPE_BLOB:
				return "BLOB";
			default:
				throw new Exception("Unable to find column type '$type'");
		}
	}

}


class PDOStatementHelper extends BddCursorHelper {
	protected $current = null;
	protected $datatype = null;
	protected $pdo;


	public function __construct($pdo, $cursor) {
		$this->pdo = $pdo;
		parent::__construct($cursor);
	}


	protected function convertType($meta) {
		switch ($meta["native_type"]) {
			case "integer":
				return ModelField::TYPE_INT;
		}
		return ModelField::TYPE_TEXT;
	}


	public function current() {
		if (!is_array($this->current))
			return $this->current;

		if ($this->datatype === null) {
			foreach(range(0, $this->cursor->columnCount() - 1) as $i) {
				$meta = $this->cursor->getColumnMeta($i);
				$this->datatype[$meta["name"]] = $this->convertType($meta);
			}
		}

		$row = array();
		foreach($this->current as $key => $val) {
			$row[$key] = $this->pdo->formatOut($this->datatype[$key], $val);
		}
		return $row;

	}


	public function key() {
		return null;
	}


	public function next() {
		$this->current = $this->cursor->fetch(PDO::FETCH_ASSOC);
	}


	public function rewind() {
		if ($this->current === null)
			$this->current = $this->cursor->fetch(PDO::FETCH_ASSOC);
		else
			throw new Exception("Cannot rewind PDOStatement");
	}


	public function valid() {
		return $this->current !== false && $this->current !== null;
	}

}
