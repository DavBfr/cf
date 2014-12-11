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

class MysqlHelper extends PDOHelper {

	protected function getParams() {
		return array(
			PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8",
			PDO::ATTR_PERSISTENT => true,
		);
	}


	protected function buildTableColumns($table_structure) {
		$columns = Array();
		foreach ($table_structure as $column) {
			$ctype = $column->getDbType();
			if (!$column->hasNull())
				$ctype .= " NOT NULL";
			if ($column->isPrimary())
				$ctype .= " PRIMARY KEY";
			if ($column->isAutoincrement()) {
				$ctype .= " AUTO_INCREMENT";
			}
			$columns[$column->getName()] = $ctype;
		}
		return $columns;
	}


	public function getTables() {
		$tables = array();
		$res = $this->query("SHOW TABLES");
		if ($res !== false) {
			while($row = $res->fetch(PDO::FETCH_NUM)) {
				$tables[] = $row[0];
			}
		}
		return $tables;
	}


	public function getTableInfo($name) {
		$fields = array();
		$res = $this->query("SHOW COLUMNS FROM `$name`");
		if ($res !== false) {
			foreach($res as $row) {
				$field = array();
				if (strpos($row["Type"], "int") !== false) $field["type"] = ModelField::TYPE_INT;
				elseif (strpos($row["Type"], "text") !== false) $field["type"] = ModelField::TYPE_TEXT;
				elseif (strpos($row["Type"], "varchar") !== false) $field["type"] = ModelField::TYPE_TEXT;
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

}
