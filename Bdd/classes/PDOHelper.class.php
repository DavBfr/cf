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

class PDOHelper {
	protected $pdo;

	public function __construct($dsn, $login, $password) {
		try {
			$this->pdo = new PDO($dsn, $login, $password, $this->getParams());
		} catch (PDOException $e) {
			ErrorHandler::error(500, NULL, "Unable to connect to Database: " . $e->getMessage());
		} catch (Exeption $e) {
			ErrorHandler::error(500, NULL, "Unable to connect to Database: " . $e->getMessage());
		}
	}


	protected function getParams() {
		return array();
	}


	public function quote($string) {
		return $this->pdo->quote($string);
	}


	public function quoteIdent($field) {
		return "`".str_replace("`","``",$field)."`";
	}


	public function lastInsertId() {
		return $this->pdo->lastInsertId();
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
		return true;
	}


	public function delete($table, $key, $value) {
		$this->query("DELETE FROM " . $table . " WHERE $key = :key", array("key" => $value));
		return true;
	}


	public function query($sql, $params = array()) {
		Logger::Debug("Query ${sql} " . json_encode($params));
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
		$ret = $this->pdo->query('SELECT 1 FROM '.$name);
		return $ret !== false;
	}


	protected function buildTableColumns($table_structure) {
		return Array();
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
		return NULL;
	}


	public function getTableInfo($name) {
		return NULL;
	}

}
