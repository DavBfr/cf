<?php

class Bdd {
	private static $instance = NULL;


	private function __construct() {
		$params = array();
		if (strpos(DBNAME, "mysql") !== false) {
			$params[PDO::MYSQL_ATTR_INIT_COMMAND] = "SET NAMES utf8";
		}

		try {
			$this->bdd = new PDO(DBNAME, DBLOGIN, DBPASSWORD, $params);
		} catch (PDOException $e) {
			ErrorHandler::error(500, NULL, "Unable to connect to Database: " . $e->getMessage());
		} catch (Exeption $e) {
			ErrorHandler::error(500, NULL, "Unable to connect to Database: " . $e->getMessage());
		}
	}


	public static function getInstance() {
		if (is_null(self::$instance)) {
			self::$instance = new self();
		}

		return self::$instance;
	}


	public function quote($string) {
		return $this->bdd->quote($string);
	}


	public function quoteIdent($field) {
		return "`".str_replace("`","``",$field)."`";
	}


	public function lastInsertId() {
		return $this->bdd->lastInsertId();
	}


	public function insert($table, $fields) {
		$this->query("INSERT INTO " .
			$table . "(" . implode(", ", array_keys($fields)) .
			") VALUES (:" . implode(", :", array_keys($fields)) . ")", $fields);
		return $this->lastInsertId();
	}


	public function update($table, $fields, $key) {
		$s = array();
		foreach($fields as $k=>$v) {
			if ($key != $k)
				$s[] = "$k = :$k";
		}
		$this->query("UPDATE " .
			$table . " SET " . implode(", ", $s) ." WHERE $key = :$key", $fields);
		return True;
	}


	public function delete($table, $key, $value) {
		$this->query("DELETE FROM " . $table . " WHERE $key = :key", array("key" => $value));
		return True;
	}


	public function query($sql, $params = array()) {
		Logger::Debug("Query ${sql} " . json_encode($params));
		$reponse = $this->bdd->prepare($sql);
		if ($reponse === false) {
			$error = $this->bdd->errorInfo();
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
		$ret = $this->bdd->query('SELECT 1 FROM '.$name);
		return $ret !== false;
	}


	private function buildTableColumns($table_structure) {
		$driver = $this->bdd->getAttribute(PDO::ATTR_DRIVER_NAME);
		$columns = Array();
		foreach ($table_structure as $column) {
			$ctype = $column->getDbType();
			if (!$column->hasNull())
				$ctype .= " NOT NULL";
			if ($column->isPrimary())
				$ctype .= " PRIMARY KEY";
			if ($column->isAutoincrement()) {
				if ($driver == "mysql") {
					$ctype .= " AUTO_INCREMENT";
				} elseif ($driver == "sqlite") {
					$ctype .= " AUTOINCREMENT";
				}
			}
			$columns[$column->getName()] = $ctype;
		}
		return $columns;
	}


	public function dropTable($name) {
		return "DROP TABLE IF EXISTS `$name`";
	}


	public function createTable($name, $table_structure) {
		$columns = $this->buildTableColumns($table_structure);
		$query  = "CREATE TABLE IF NOT EXISTS `${name}` (\n  ";
		$cols = array();
		foreach ($columns as $column_name => $column_type) {
			$cols[] = '`'.$column_name.'` '.$column_type;
		}
		$query .= implode(",\n  ", $cols);
		$query .= "\n)";
		return $query;
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
