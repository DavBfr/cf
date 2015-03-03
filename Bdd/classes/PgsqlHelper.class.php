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

class PgsqlHelper extends PDOHelper {

	protected function getParams() {
		return array(
			PDO::ATTR_PERSISTENT => true,
		);
	}
	
	public function quoteIdent($field) {
		return $field;
	}


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


	protected function buildTableColumns($table_structure) {
		$columns = Array();
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
			$columns[$column->getName()] = $ctype;
		}
		return $columns;
	}


	public function getTables() {
		$tables = array();
		$res = $this->query("SELECT * FROM pg_catalog.pg_tables WHERE schemaname='public'");
		if ($res !== false) {
			while($row = $res->fetch()) {
				$tables[] = $row['tablename'];
			}
		}
		return $tables;
	}


	public function getTableInfo($name) {
		$fields = array();
		$res = $this->query("SELECT * FROM information_schema.columns WHERE table_name ='$name'");
		if ($res !== false) {
			foreach($res as $row) {
				$field = array();
				if (strpos($row["data_type"], "int") !== false) $field["type"] = ModelField::TYPE_INT;
				elseif (strpos($row["data_type"], "text") !== false) $field["type"] = ModelField::TYPE_TEXT;
				elseif (strpos($row["data_type"], "varchar") !== false) $field["type"] = ModelField::TYPE_TEXT;
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


	public function getQueryString($fields, $tables, $joint, $where, $filter, $order, $group, $params, $limit, $pos, $distinct) {
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
			$query .= " ".implode(" ", $joint);
		}

		if ($filter) {
			$value = $this->quote("%".$filter."%");
			
			$filter = array();
			foreach ($fields as $field) {
				$filter[] = $this->quoteIdent($field) . " LIKE " . $value;
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
			$query .= ' OFFSET ' . ($pos * $limit) ." LIMIT " . $limit;

		return $query;
	}

}
