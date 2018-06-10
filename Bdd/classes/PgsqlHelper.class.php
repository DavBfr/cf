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

use PDO;

class PgsqlHelper extends PDOHelper {

	/**
	 * @return array
	 */
	protected function getParams() {
		return array(
			PDO::ATTR_PERSISTENT => true,
		);
	}


	/**
	 * @param string $field
	 * @return string
	 */
	public function quoteIdent($field) {
		return $field;
	}


	/**
	 * @param string $type
	 * @return string
	 * @throws \Exception
	 */
	protected function getDbType($type) {
		switch ($type) {
			case ModelField::TYPE_DECIMAL:
				return "NUMERIC";
			case ModelField::TYPE_BLOB:
				return "bytea";
			default:
				return parent::getDbType($type);
		}
	}


	/**
	 * @param array $table_structure
	 * @return array
	 * @throws \Exception
	 */
	protected function buildTableColumns(array $table_structure) {
		$columns = array();
		foreach ($table_structure as $column) {
			if ($column->isAutoincrement()) {
				$ctype = "SERIAL";
			} else {
				$ctype = $this->getDbType($column->getType());
			}
			if (!$column->hasNull())
				$ctype .= " NOT NULL";
			if ($column->isPrimary())
				$ctype .= " PRIMARY KEY";
			$default = $column->getDefault();
			if ($default !== null) {
				if (is_bool($default)) $default = intval($default);
				if (is_string($default)) $default = $this->quote($default);
				$ctype .= " DEFAULT " . $default;
			}
			$columns[$column->getName()] = $ctype;
		}
		return $columns;
	}


	/**
	 * @return string[]
	 * @throws \Exception
	 */
	public function getTables() {
		$tables = array();
		$res = $this->query("SELECT * FROM pg_catalog.pg_tables WHERE schemaname='public'");
		if ($res !== false) {
			while ($row = $res->fetch()) {
				$tables[] = $row['tablename'];
			}
		}
		return $tables;
	}


	/**
	 * @param string $name
	 * @return array
	 * @throws \Exception
	 */
	public function getTableInfo($name) {
		$fields = array();
		$res = $this->query("SELECT * FROM information_schema.columns WHERE table_name ='$name'");
		if ($res !== false) {
			foreach ($res as $row) {
				$field = array();
				if (strpos($row["data_type"], "int") !== false) $field["type"] = ModelField::TYPE_INT;
				elseif (strpos($row["data_type"], "text") !== false) $field["type"] = ModelField::TYPE_TEXT;
				elseif (strpos($row["data_type"], "varchar") !== false) $field["type"] = ModelField::TYPE_TEXT;
				elseif (strpos($row["Type"], "datetime") !== false) $field["type"] = ModelField::TYPE_DATETIME;
				elseif (strpos($row["Type"], "time") !== false) $field["type"] = ModelField::TYPE_TIME;
				elseif (strpos($row["data_type"], "date") !== false) $field["type"] = ModelField::TYPE_DATE;
				elseif (strpos($row["data_type"], "timestamp") !== false) $field["type"] = ModelField::TYPE_TIMESTAMP;
				else $field["type"] = $row["data_type"];

				$field["null"] = $row["is_nullable"] == "YES";

				if (strpos($row["column_default"], "nextval") !== false) {
					$field["primary"] = true;
					$field["autoincrement"] = true;
					$field["type"] = ModelField::TYPE_AUTO;
				} else {
					$field["default"] = $row["column_default"];
				}
				$fields[$row["column_name"]] = $field;
			}
		}
		return $fields;
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
			$query .= " " . implode(" ", $joint);
		}

		if ($filter) {
			$value = $this->quote("%" . $filter . "%");

			$filter = array();
			foreach ($fields as $field) {
				$filter[] = $this->quoteIdent($field) . " LIKE " . $value;
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
			$query .= ' OFFSET ' . ($pos * $limit) . " LIMIT " . $limit;

		return $query;
	}

}
