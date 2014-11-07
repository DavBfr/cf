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

class SqliteHelper extends PDOHelper {

	protected function buildTableColumns($table_structure) {
		$columns = Array();
		foreach ($table_structure as $column) {
			$ctype = $column->getDbType();
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


	public function getTables() {
		$tables = array();
		$res = $this->query("SELECT tbl_name FROM sqlite_master WHERE type='table'");
		if ($res !== false) {
			while($row = $res->fetch(PDO::FETCH_NUM)) {
				if ($row[0] != "sqlite_sequence")
					$tables[] = $row[0];
			}
		}
		return $tables;
	}


	public function getTableInfo($name) {
		$auto = false;
		$res = $this->query("select count(*) from sqlite_sequence where name=".$this->quote($name));
		if ($res !== false) {
			$row = $res->fetch(PDO::FETCH_NUM);
			if ($row[0] == 1)
				$auto = true;
		}

		$fields = array();
		$res = $this->query("pragma table_info($name)");
		if ($res !== false) {
			foreach($res as $row) {
				$field = array();
				if (strpos($row["type"], "INTEGER") !== False) $field["type"] = "int";
				elseif (strpos($row["type"], "TEXT") !== False) $field["type"] = "text";
				elseif (strpos($row["type"], "DATE") !== False) $field["type"] = "date";
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

}
