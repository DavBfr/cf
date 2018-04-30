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

class SqliteHelper extends PDOHelper {

	/**
	 * @param string $format
	 * @param string $date
	 * @return string
	 */
	public function strftime($format, $date) {
		return "strftime('$format', $date)";
	}


	/**
	 * @param array $table_structure
	 * @return array
	 * @throws \Exception
	 */
	protected function buildTableColumns(array $table_structure) {
		$columns = array();
		foreach ($table_structure as $column) {
			$ctype = $this->getDbType($column->getType());
			if (!$column->hasNull())
				$ctype .= " NOT NULL";
			if ($column->isPrimary())
				$ctype .= " PRIMARY KEY";
			if ($column->isAutoincrement()) {
				$ctype .= " AUTOINCREMENT";
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
		$res = $this->query("SELECT tbl_name FROM sqlite_master WHERE type='table'");
		if ($res !== false) {
			while ($row = $res->fetch(PDO::FETCH_NUM)) {
				if ($row[0] != "sqlite_sequence")
					$tables[] = $row[0];
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
		$auto = false;
		$res = $this->query("select count(*) from sqlite_sequence where name=" . $this->quote($name));
		if ($res !== false) {
			$row = $res->fetch(PDO::FETCH_NUM);
			if ($row[0] == 1)
				$auto = true;
		}

		$fields = array();
		$res = $this->query("pragma table_info($name)");
		if ($res !== false) {
			foreach ($res as $row) {
				$field = array();
				if (strpos(strtoupper($row["type"]), "INTEGER") !== false) $field["type"] = ModelField::TYPE_INT;
				elseif (strpos(strtoupper($row["type"]), "TEXT") !== false) $field["type"] = ModelField::TYPE_TEXT;
				elseif (strpos(strtoupper($row["Type"]), "DATETIME") !== false) $field["type"] = ModelField::TYPE_DATETIME;
				elseif (strpos(strtoupper($row["Type"]), "TIME") !== false) $field["type"] = ModelField::TYPE_TIME;
				elseif (strpos(strtoupper($row["type"]), "DATE") !== false) $field["type"] = ModelField::TYPE_DATE;
				else $field["type"] = $row["type"];

				$field["null"] = $row["notnull"] == 0;
				$field["primary"] = $row["pk"] == 1;
				$field["default"] = $row["dflt_value"];
				$field["autoincrement"] = $auto && $field["primary"];
				$fields[$row["name"]] = $field;
			}
		}

		return $fields;
	}


	/**
	 * @param array $fields
	 * @param array $tables
	 * @param array $joint
	 * @param array $where
	 * @param array $filter
	 * @param array $filter_fields
	 * @param array $order
	 * @param array $group
	 * @param array $params
	 * @param int $limit
	 * @param $pos
	 * @param $distinct
	 * @return PDOStatementHelper|SqliteStatementHelper
	 * @throws \Exception
	 */
	public function getQueryValues(array $fields, array $tables, array $joint, array $where, array $filter, array $filter_fields, array $order, array $group, array $params, $limit, $pos, $distinct) {
		$sql = $this->getQueryString($fields, $tables, $joint, $where, $filter, $filter_fields, $order, $group, $params, $limit, $pos, $distinct);
		return new SqliteStatementHelper($this, $this->query($sql, $params));
	}
}


class SqliteStatementHelper extends PDOStatementHelper {
	/**
	 * @param array $meta
	 * @return string
	 */
	protected function convertType($meta) {
		switch ($meta["sqlite:decl_type"]) {
			case "NUMBER":
				return ModelField::TYPE_DECIMAL;
			case "INTEGER":
				return ModelField::TYPE_INT;
			case "DATETIME":
				return ModelField::TYPE_DATETIME;
			case "DATE":
				return ModelField::TYPE_DATE;
			case "TIME":
				return ModelField::TYPE_TIME;
			case "BLOB":
				return ModelField::TYPE_BLOB;
		}
		return parent::convertType($meta);
	}
}
