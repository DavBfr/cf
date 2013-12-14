<?php

configure("DBNAME", 'sqlite:' . DATA_DIR . '/db.sqlite');
configure("DBLOGIN", '');
configure("DBPASSWORD", '');

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
			send_error(500, NULL, "Unable to connect to Database " . $e->getMessage());
		} catch (Exeption $e) {
			send_error(500, NULL, "Unable to connect to Database " . $e->getMessage());
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


	public function query($sql, $params = array()) {
		Logger::Debug("Query ${sql} " . json_encode($params));
		$reponse = $this->bdd->prepare($sql);
		if ($reponse === false) {
			$error = $this->bdd->errorInfo();
			send_error(500, NULL, "Error in SQL statement ${error[0]} (${error[1]}) ${error[2]} in\n$sql");
		}
		$reponse->setFetchMode(PDO::FETCH_NAMED);
		$result = $reponse->execute($params);
		if ($result === false) {
			$error = $reponse->errorInfo();
			send_error(500, NULL, "Sql error ${error[0]} (${error[1]}) ${error[2]} in\n$sql");
		}
		return $reponse;
	}


	public function select($fields, $tables, $where = array(1), $limit=NULL, $order=NULL) {
		$query = "SELECT ";
		if (is_array($fields))
			$query .= implode(", ", $fields);
		else
			$query .= $fields;
		$query .= " FROM ";
		if (is_array($tables))
			$query .= implode(", ", $tables);
		else
			$query .= $tables;
		$query .= " WHERE (";
		$query .= implode(") AND (", $where);
		$query .= ")";
		if ($order) {
			$query .= ' ORDER BY '. implode(", ", $order);
		}
		if ($limit) {
			$query .= ' LIMIT '. implode(", ", $limit);
		}
		return $query;
	}


	public function tableExists($name) {
		$ret = $this->bdd->query('SELECT 1 FROM '.$name);
		return $ret !== false;
	}


	private function buildTableColumns($table_structure) {
		$driver = $this->bdd->getAttribute(PDO::ATTR_DRIVER_NAME);
		$columns = Array();
		foreach ($table_structure as $column_name => $column) {
			$column_type = explode(',', $column["type"]);
			$ctype = $column_type[0];
			if (in_array("int", $column_type))
				$ctype = "INTEGER";
			if (in_array("bool", $column_type))
				$ctype = "INTEGER";
			elseif (in_array("text", $column_type))
				$ctype = "TEXT";
			elseif (in_array("date", $column_type))
				$ctype = "DATE";
			if (in_array("not null", $column_type))
				$ctype .= " NOT NULL";
			if ($column["primary"])
				$ctype .= " PRIMARY KEY";
			if ($column["autoincrement"]) {
				if ($driver == "mysql") {
					$ctype .= " AUTO_INCREMENT";
				} elseif ($driver == "sqlite") {
					$ctype .= " AUTOINCREMENT";
				}
			}
			$columns[$column_name] = $ctype;
		}
		return $columns;
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


	public function buildTable($name, $table_structure) {
		$columns = Array();
		foreach ($table_structure as $column_name => $column) {
			$column_type = explode(',', $column["type"]);
			$ctype = $column_type[0];
			if (in_array("int", $column_type))
				$ctype = "INTEGER";
			elseif (in_array("text", $column_type))
				$ctype = "INTEGER";
			elseif (in_array("date", $column_type))
				$ctype = "DATE";
			if (in_array("not null", $column_type))
				$ctype .= " NOT NULL";
			if ($column["primary"])
				$ctype .= " PRIMARY KEY";
			if ($column["autoincrement"])
				$ctype .= " AUTOINCREMENT";
			$columns[$column_name] = $ctype;
		}
		// the table exists ?
		$table_exists = false;
		$ret = $this->bdd->query('SELECT 1 FROM '.$name);
		if ($ret !== false) {
			$table_exists =  true;
		}
		if (!$table_exists) {
			$query  = 'CREATE TABLE IF NOT EXISTS `'.$name.'` (';
			foreach ($columns as $column_name => $column_type) {
				$query .= '`'.$column_name.'` '.$column_type.' , ';
			}
			$query = substr($query, 0, -3);
			$query .= ')';
			$ret = $this->bdd->exec($query);
			if ($ret === false) {
				$error = $this->bdd->errorInfo();
				print("Error in SQL statement: ${error[0]} (${error[1]}) ${error[2]} in $query");
			}
			return $ret;
		} else {
			$query_queue = array();

			// the table exists, it is the right structure ?
			$res = $this->query('PRAGMA table_info('.$name.')');
			if ($res !== false) {
				$columns_from_database = array();
				foreach($res as $row) {
					print_r($row);
					if (in_array( $row['name'], array_keys($table_structure))) {
						// the column exists
						// it's the same type ?
						$ret2 = $this->DoQuery('SHOW COLUMNS FROM #1 WHERE @2=%3', $name_, 'Field', $row['Field']);
						if ($ret2 === false) {
							Logger::error('main', 'SQL::createTable failed to get type of \''.$row['Field'].'\'');
							return false;
						}
						$rows6 = $this->FetchResult();
						$field_type = $rows6['Type'];
						$type6 = explode(' ', $table_structure_[$row['Field']]);
						if (isset($type6[0])) {
							if (strtoupper($type6[0]) !== strtoupper($field_type)) {
								// it's not the same -> we will alter the table
								$query_queue[] = 'MODIFY `'.$row['Field'].'` '.$table_structure_[$row['Field']];
							}
						}
						$columns_from_database[] = $row['Field'];
					}
					else {
						// we must remove this column
						$query_queue[] = 'DROP `'.$row['Field'].'`';
					}
				}

				foreach($table_structure_ as $column_name => $column_structure) {
					if (!in_array($column_name, $columns_from_database)) {
						$query_queue[] = 'ADD `'.$column_name.'` '.$column_structure;
					}
				}
			}

			// look for indexes structure
			$keys = array();
			$res = $this->DoQuery('SHOW INDEXES FROM #1', $name_);
			if ($res !== false) {
				$rows = $this->FetchAllResults($res);
				foreach($rows as $row) {
					if (!array_key_exists($row['Key_name'], $keys))
						$keys[$row['Key_name']] = array();

					$keys[$row['Key_name']][] = $row['Column_name'];
				}
			}

			$process_indexes = false;

			if ((count($primary_keys_) > 0) != array_key_exists('PRIMARY', $keys))
				$process_indexes = true;

			foreach($indexes_ as $name=>$index) {
				if ((count($index) > 0) != array_key_exists($name, $keys)) {
					$process_indexes = true;
					break;
				}

				if (array_diff($keys[$name], $index) != array_diff($index, $keys[$name])) {
					$process_indexes = true;
					break;
				}
			}

			if (!$process_indexes && array_key_exists('PRIMARY', $keys) && array_diff($keys['PRIMARY'], $primary_keys_) != array_diff($primary_keys_, $keys['PRIMARY']))
				$process_indexes = true;

			// is the database to be updated ?
			if (count($query_queue) > 0 || $process_indexes) {
				// drop all indexes
				foreach(array_keys($keys) as $key) {
					if ($key == "PRIMARY")
						$key .= " KEY";
					else
						$key = "KEY ".$key;

					array_unshift($query_queue, 'DROP '.$key);
				}

				// add primary key
				if (count($primary_keys_) > 0)
					$query_queue[] = 'ADD PRIMARY KEY  (`'.implode('`, `', $primary_keys_).'`)';

				// add indexes
				foreach($indexes_ as $name=>$index) {
					if (substr($name, 0, 2) === 'U_')
						$query_queue[] = 'ADD  UNIQUE '.$name.' (`'.implode('`, `', $index).'`)';
					else
						$query_queue[] = 'ADD  INDEX '.$name.' (`'.implode('`, `', $index).'`)';
				}

				$res = $this->DoQuery('ALTER TABLE #1 '.implode(', ', $query_queue), $name_);
				if ($res == false)
					Logger::error('main', 'SQL::createTable failed to modify table '.$name_);
			}
			return true;
		}
	}

}
