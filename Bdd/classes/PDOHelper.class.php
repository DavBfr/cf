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
use PDO;
use PDOException;

class PDOHelper extends BddHelper {
	protected $pdo;


	/**
	 * PDOHelper constructor.
	 * @param string $dsn
	 * @param string $login
	 * @param string $password
	 * @throws Exception
	 */
	public function __construct($dsn, $login, $password) {
		try {
			$this->pdo = new PDO($dsn, $login, $password, $this->getParams());
			$this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
		} catch (PDOException $e) {
			ErrorHandler::error(500, null, "Unable to connect to Database: " . $e->getMessage());
		} catch (Exception $e) {
			ErrorHandler::error(500, null, "Unable to connect to Database: " . $e->getMessage());
		}
	}


	/**
	 * @param string $string
	 * @return string
	 */
	public function quote($string) {
		return $this->pdo->quote($string);
	}


	/**
	 * @param string $field
	 * @return string
	 */
	public function quoteIdent($field) {
		return "`" . str_replace("`", "``", $field) . "`";
	}


	/**
	 * @param string $table
	 * @param array $fields
	 * @return int
	 * @throws Exception
	 */
	public function insert($table, array $fields) {
		$this->query("INSERT INTO " .
			$table . "(" . implode(", ", array_keys($fields)) .
			") VALUES (:" . implode(", :", array_keys($fields)) . ")", $fields);
		return $this->pdo->lastInsertId();
	}


	/**
	 * @param string $table
	 * @param array $fields
	 * @param string $key
	 * @return bool
	 * @throws Exception
	 */
	public function update($table, array $fields, $key) {
		$s = array();
		foreach ($fields as $k => $v) {
			if ($key != $k)
				$s[] = "$k = :$k";
		}
		$this->query("UPDATE " .
			$table . " SET " . implode(", ", $s) . " WHERE $key = :$key", $fields);
		return true;
	}


	/**
	 * @param string $table
	 * @param string $key
	 * @param string $value
	 * @return bool
	 * @throws Exception
	 */
	public function delete($table, $key, $value) {
		$this->query("DELETE FROM " . $table . " WHERE $key = :key", array("key" => $value));
		return true;
	}


	/**
	 * @param string $sql
	 * @param array $params
	 * @return bool|\PDOStatement
	 * @throws Exception
	 */
	public function query($sql, $params = array()) {
		Logger::debug("Query ${sql} ", $params);
		$response = $this->pdo->prepare($sql);
		if ($response === false) {
			$error = $this->pdo->errorInfo();
			ErrorHandler::error(500, null, "Error in SQL statement ${error[0]} (${error[1]}) ${error[2]} in\n$sql");
		}
		$response->setFetchMode(PDO::FETCH_NAMED);
		$result = $response->execute($params);
		if ($result === false) {
			$error = $response->errorInfo();
			ErrorHandler::error(500, null, "Sql error ${error[0]} (${error[1]}) ${error[2]} in\n$sql");
		}
		return $response;
	}


	public function tableExists($name) {
		try {
			$ret = $this->pdo->query('SELECT 1 FROM ' . $name);
			return $ret !== false;
		} catch (PDOException $e) {
			return false;
		}
	}


	/**
	 * @param array $table_structure
	 * @return array
	 */
	protected function buildTableColumns(array $table_structure) {
		return array();
	}


	/**
	 * @param string $name
	 * @return string
	 */
	public function dropTableQuery($name) {
		return "DROP TABLE IF EXISTS " . $this->quoteIdent($name);
	}


	/**
	 * @param string $name
	 * @return bool|void
	 * @throws Exception
	 */
	public function dropTable($name) {
		$this->query($this->dropTableQuery($name));
	}


	/**
	 * @param string $name
	 * @param array $table_structure
	 * @return string
	 */
	public function createTableQuery($name, array $table_structure) {
		$columns = $this->buildTableColumns($table_structure);
		$query = "CREATE TABLE IF NOT EXISTS " . $this->quoteIdent($name) . " (\n  ";
		$cols = array();
		foreach ($columns as $column_name => $column_type) {
			$cols[] = $this->quoteIdent($column_name) . ' ' . $column_type;
		}
		$query .= implode(",\n  ", $cols);
		$query .= "\n)";
		return $query;
	}


	/**
	 * @param string $name
	 * @param array $table_structure array of ModelField
	 * @return string[]
	 * @throws Exception
	 */
	public function alterTableQuery($name, array $table_structure) {
		$sql = array();
		$actual_structure = $this->getTableInfo($name);

		$alter = false;
		foreach ($actual_structure as $field => $actual_field) {
			$actual = new ModelField($name, $field, $actual_field);

			if (!array_key_exists($field, $table_structure)) {
				Logger::warning("     $field not found in structure");
				$alter = true;
				break;
			}

			$target = $table_structure[$field];
			if ($this->getDbType($actual->getType()) != $this->getDbType($target->getType()) ||
				$actual->hasNull() != $target->hasNull() ||
				$actual->isPrimary() != $target->isPrimary() ||
				$actual->isAutoincrement() != $target->isAutoincrement() ||
				$actual->getDefault() != $target->getDefault()) {
				Logger::warning("     $field different in structure");
				Logger::debug("Type:", $this->getDbType($actual->getType()), $this->getDbType($target->getType()));
				Logger::debug("Null:", $actual->hasNull(), $target->hasNull());
				Logger::debug("Primary:", $actual->isPrimary(), $target->isPrimary());
				Logger::debug("Autoincrement:", $actual->isAutoincrement(), $target->isAutoincrement());
				Logger::debug("Default:", $actual->getDefault(), $target->getDefault());
				$alter = true;
				break;
			}
		}

		foreach ($table_structure as $field => $target) {
			if (!array_key_exists($field, $actual_structure)) {
				Logger::warning("     $field not found in database");
				$alter = true;
				break;
			}
		}

		if ($alter) {
			$tmpname = "__{$name}_alter_PDO";
			$sql[] = $this->createTableQuery($tmpname, $table_structure);

			$fields = array();
			foreach ($table_structure as $key => $structure) {
				if (array_key_exists($key, $actual_structure)) {
					$fields[] = $this->quoteIdent($key);
				} else {
					$default = $structure->getDefault();
					if ($default === null && $structure->hasNull()) {
						$fields[] = 'NULL';
					} elseif ($default === null) {
						switch ($structure->getType()) {
							case ModelField::TYPE_INT:
								$fields[] = 0;
								break;
							case ModelField::TYPE_TIMESTAMP:
								$fields[] = time();
								break;
							default:
								$fields[] = $this->quote('');
								break;
						}
					} else {
						$fields[] = is_numeric($default) ? $default : $this->quote($default);
					}
				}
			}

			$fields = implode(", ", $fields);
			$sql[] = "INSERT INTO " . $this->quoteIdent($tmpname) . " SELECT $fields FROM " . $this->quoteIdent($name);
			$sql[] = "DROP TABLE " . $this->quoteIdent($name);
			$sql[] = "ALTER TABLE " . $this->quoteIdent($tmpname) . " RENAME TO " . $this->quoteIdent($name);
		}

		return $sql;
	}


	/**
	 * @param string $name
	 * @param array $table_structure
	 * @throws Exception
	 */
	public function createTable($name, array $table_structure) {
		$this->query($this->createTableQuery($name, $table_structure));
	}


	/**
	 * @param string $name
	 * @param array $table_structure
	 * @throws Exception
	 */
	public function alterTable($name, array $table_structure) {
		$queries = $this->alterTableQuery($name, $table_structure);
		if (count($queries) == 0) return;

		if (Options::get('DATABASE_ALTER_CONFIRMATION')) {
			Cli::perr("The table '$name' has been modified.");
			Cli::pinfo("the following SQL statements have to be executed on the server:");
			Cli::pln(implode(";\n", $queries) . ";");

			Cli::question("Do you want to perform the change to the table '$name'?");
		}

		foreach ($queries as $sql) {
			$this->query($sql);
		}
	}


	/**
	 * @return string[]
	 */
	public function getTables() {
		return array();
	}


	/**
	 * @param string $name
	 * @return array
	 */
	public function getTableInfo($name) {
		return array();
	}


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
	public function getQueryString(array $fields, array $tables, array $joint, array $where, $filter, array $filter_fields, array $order, array $group, array $params, $limit, $pos, $distinct) {
		$query = "SELECT " . ($distinct ? "DISTINCT " : "");

		if (count($fields) == 0)
			$query .= "*";
		else {
			$_fields = array();
			foreach ($fields as $k => $v) {
				if (is_int($k))
					$_fields[] = $v;
				else
					$_fields[] = "$v as $k";
			}
			$query .= implode(", ", $_fields);

		}

		$query .= " FROM " . implode(", ", $tables);

		if (count($joint) > 0) {
			$joints = array();

			foreach ($joint as $k => $v) {
				$joints[] = "LEFT JOIN ${v[0]} ON ${v[1]}";
			}
			$query .= " " . implode(" ", $joints);
		}

		if ($filter) {
			$value = $this->quote("%" . $filter . "%");

			$filter = array();
			if ($filter_fields == null) {
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
			$query .= " WHERE (" . implode(") AND (", $where) . ")";

		if (count($group) > 0)
			$query .= " GROUP BY " . implode(", ", $group);

		if (count($order) > 0)
			$query .= ' ORDER BY ' . implode(", ", $order);

		if ($limit)
			$query .= ' LIMIT ' . ($pos * $limit) . ", " . $limit;

		return $query;
	}


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
	 * @param $pos
	 * @param $distinct
	 * @return PDOStatementHelper
	 * @throws Exception
	 */
	public function getQueryValues(array $fields, array $tables, array $joint, array $where, $filter, array $filter_fields, array $order, array $group, array $params, $limit, $pos, $distinct) {
		$sql = $this->getQueryString($fields, $tables, $joint, $where, $filter, $filter_fields, $order, $group, $params, $limit, $pos, $distinct);
		return new PDOStatementHelper($this, $this->query($sql, $params));
	}


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
	 * @throws Exception
	 */
	public function getQueryValuesArray(array $fields, array $tables, array $joint, array $where, $filter, array $filter_fields, array $order, array $group, array $params, $limit, $pos, $distinct) {
		$collection = array();
		$result = $this->getQueryValues($fields, $tables, $joint, $where, $filter, $filter_fields, $order, $group, $params, $limit, $pos, $distinct);
		foreach ($result as $row) {
			$collection[] = $row;
		}
		return $collection;
	}


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
	 * @throws Exception
	 */
	public function getQueryCount(array $tables, array $joint, array $where, $filter, array $filter_fields, array $group, array $params, $distinct) {
		$sql = $this->getQueryString(array("COUNT(*)"), $tables, $joint, $where, $filter, $filter_fields, array(), $group, $params, null, 0, $distinct);
		$values = $this->query($sql, $params);
		$count = $values->fetch(PDO::FETCH_NUM);
		return intval($count[0]);
	}


	/**
	 * @param string $type
	 * @return string
	 * @throws Exception
	 */
	protected function getDbType($type) {
		switch ($type) {
			case ModelField::TYPE_INT:
			case ModelField::TYPE_TIMESTAMP:
			case ModelField::TYPE_BOOL:
				return "INTEGER";
			case ModelField::TYPE_DECIMAL:
				return "NUMBER";
			case ModelField::TYPE_TEXT:
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


	/**
	 * PDOStatementHelper constructor.
	 * @param PDOHelper $pdo
	 * @param \PDOStatement $cursor
	 */
	public function __construct(PDOHelper $pdo, \PDOStatement $cursor) {
		$this->pdo = $pdo;
		parent::__construct($cursor);
	}


	/**
	 * @param array $meta
	 * @return string
	 */
	protected function convertType($meta) {
		switch (strtolower($meta["native_type"])) {
			case "integer":
			case "long":
			case "longlong":
			case "int":
				return ModelField::TYPE_INT;
			case "double":
				return ModelField::TYPE_DECIMAL;
			case "date":
				return ModelField::TYPE_DATE;
			case "time":
				return ModelField::TYPE_DATE;
			case "blob":
				return ModelField::TYPE_BLOB;
			case "bool":
				return ModelField::TYPE_BOOL;
		}
		return ModelField::TYPE_TEXT;
	}


	/**
	 * @return array|null
	 */
	public function current(): mixed {
		if (!is_array($this->current))
			return $this->current;

		if ($this->datatype === null) {
			foreach (range(0, $this->cursor->columnCount() - 1) as $i) {
				$meta = $this->cursor->getColumnMeta($i);
				$this->datatype[$meta["name"]] = $this->convertType($meta);
			}
		}

		$row = array();
		foreach ($this->current as $key => $val) {
			$row[$key] = $this->pdo->formatOut($this->datatype[$key], $val);
		}

		return $row;
	}


	/**
	 * @return mixed
	 */
	public function key(): mixed {
		return null;
	}


	public function next(): void {
		$this->current = $this->cursor->fetch(PDO::FETCH_ASSOC);
	}


	/**
	 * @throws Exception
	 */
	public function rewind(): void {
		if ($this->current === null)
			$this->current = $this->cursor->fetch(PDO::FETCH_ASSOC);
		else
			throw new Exception("Cannot rewind PDOStatement");
	}


	/**
	 * @return bool
	 */
	public function valid(): bool {
		return $this->current !== false && $this->current !== null;
	}

}
