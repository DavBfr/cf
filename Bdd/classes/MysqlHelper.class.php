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

class MysqlHelper extends PDOHelper {

	/**
	 * @return array
	 */
	protected function getParams() {
		return array(
			PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8",
			PDO::ATTR_PERSISTENT => true,
		);
	}


	/**
	 * @param string $format
	 * @param string $date
	 * @return string
	 */
	public function strftime($format, $date) {
		return "DATE_FORMAT($date, '$format')";
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
				$ctype .= " AUTO_INCREMENT";
			}
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
		$res = $this->query("SHOW TABLES");
		if ($res !== false) {
			while ($row = $res->fetch(PDO::FETCH_NUM)) {
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
		$fields = array();
		$res = $this->query("SHOW COLUMNS FROM `$name`");
		if ($res !== false) {
			foreach ($res as $row) {
				$field = array();
				if (strpos($row["Type"], "int") !== false) $field["type"] = ModelField::TYPE_INT;
				elseif (strpos($row["Type"], "text") !== false) $field["type"] = ModelField::TYPE_TEXT;
				elseif (strpos($row["Type"], "varchar") !== false) $field["type"] = ModelField::TYPE_TEXT;
				elseif (strpos($row["Type"], "datetime") !== false) $field["type"] = ModelField::TYPE_DATETIME;
				elseif (strpos($row["Type"], "time") !== false) $field["type"] = ModelField::TYPE_TIME;
				elseif (strpos($row["Type"], "date") !== false) $field["type"] = ModelField::TYPE_DATE;
				elseif (strpos($row["Type"], "timestamp") !== false) $field["type"] = ModelField::TYPE_TIMESTAMP;
				else $field["type"] = $row["Type"];

				$field["null"] = $row["Null"] == "YES";
				$field["primary"] = strpos($row["Key"], "PRI") !== false;
				$field["default"] = $row["Default"];
				$field["autoincrement"] = strpos($row["Extra"], "auto_increment") !== false;
				$fields[$row["Field"]] = $field;
			}
		}
		return $fields;
	}


	/**
	 * @param string $type
	 * @return string
	 * @throws \Exception
	 */
	protected function getDbType($type) {
		switch ($type) {
			case ModelField::TYPE_BLOB:
				return "LONGBLOB";
			default:
				return parent::getDbType($type);
		}
	}


}
