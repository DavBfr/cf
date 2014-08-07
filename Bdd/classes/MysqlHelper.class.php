<?php

class MysqlHelper extends PDOHelper {

	protected function getParams() {
		return array_merge(parent::getParams(), array(
			PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8",
			PDO::ATTR_PERSISTENT => true,
		));
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
				if (strpos($row["Type"], "int") !== False) $field["type"] = "int";
				elseif (strpos($row["Type"], "text") !== False) $field["type"] = "text";
				elseif (strpos($row["Type"], "varchar") !== False) $field["type"] = "text";
				elseif (strpos($row["Type"], "date") !== False) $field["type"] = "date";
				elseif (strpos($row["Type"], "timestamp") !== False) $field["type"] = "date";
				else $field["type"] = $row["Type"];

				$field["null"] = $row["Null"] == "YES";
				$field["primary"] = strpos($row["Key"], "PRI") !== False;
				$field["default"] = $row["Default"];
				$field["autoincrement"] = strpos($row["Extra"], "auto_increment") !== False;
				$fields[$row["Field"]] = $field;
			}
		}
		return $fields;
	}

}
