<?php
/**
 * Copyright (C) 2013-2015 David PHAM-VAN
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

/**
 * https://github.com/etconsilium/pdo-mongodb/blob/master/pdo/mongodb.php
 * http://php.net/mongo
 * http://blog.mongodb.org/post/24960636131/mongodb-for-the-php-mind-part-1
 * http://www.querymongo.com
 **/

class MongodbHelper extends BddHelper {
	protected $mongo;
	protected $db;

	public function __construct($dsn, $login, $password) {
		try {
			Logger::Debug("connect to ($dsn)");
			$this->mongo = new MongoClient($dsn);
			$dbname = basename($dsn);
			$this->db = $this->mongo->selectDB($dbname);
		} catch (Exception $e) {
			ErrorHandler::error(500, NULL, "Unable to connect to Database: " . $e->getMessage());
		}
	}


	public function quote($string) {
		if (is_numeric($string))
			return $string;
		if (is_bool($string))
			return $string ? "0" : "1";
		if (is_scalar($string))
			return "'" . preg_quote((string)$string, "'") . "'";
		return (array) $string;
	}


	public function quoteIdent($field) {
		return $field;
	}


	public function insert($table, $fields) {
		Logger::Debug("Insert $table", $fields);
		$collection = $this->db->selectCollection($table);
		$collection->insert($fields);
		return ((string)$fields["_id"]);
	}


	public function update($table, $fields, $key) {
		Logger::Debug("Update $table", $fields, "$key");
		$criteria = array();
		if (isset($fields["_id"])) {
			$fields["_id"] = new MongoID($fields["_id"]);
		}
		foreach($fields as $k=>$v) {
			if ($key == $k)  {
				$criteria[$k] = $v;
				unset($fields[$k]);
			}
		}
		$collection = $this->db->selectCollection($table);
		Logger::Debug("Update criteria", $criteria, "values", $fields, $key);
		$collection->update($criteria, $fields);
		return NULL;
	}


	public function delete($table, $key, $value) {
		Logger::Debug("Delete $table, $key, $value");
		$collection = $this->db->selectCollection($table);
		if ($key == "_id") {
			$value = new MongoID($value);
		}
		$collection->remove(array($key=>$value));
		return NULL;
	}


	public function tableExists($name) {
		return true;
	}


	protected function buildTableColumns($table_structure) {
		Logger::Debug("buildTableColumns($table_structure)");
		return Array();
	}


	public function dropTableQuery($name) {
		return false;
	}


	public function dropTable($name) {
		Logger::Debug("dropTable($name)");
		$collection = $this->db->selectCollection($name);
		$collection->drop();
	}


	public function createTableQuery($name, $table_structure) {
		return false;
	}


	public function createTable($name, $table_structure) {
		Logger::Debug("createTable($name, " . json_encode($table_structure) . ")");
	}


	public function getTables() {
		Logger::Debug("getTables");
		return $this->db->getCollectionNames();
	}


	public function getTableInfo($name) {
		Logger::Debug("getTableInfo ($name)");
		return false;
	}


	public function getQueryString($fields, $tables, $joint, $where, $filter, $filter_fields, $order, $group, $params, $limit, $pos, $distinct) {
		return false;
	}

	
	private function createQuery($where, $filter, $filter_fields, $group, $params) {
		$query = array();
		if ($filter) {
			$value = "/".$filter."/";
			
			$filter = array();
			foreach ($fields as $field) {
				$filter[] = $this->quoteIdent($field) . " LIKE " . $value;
			}
			if (count($filter) > 0) {
				$where[] = implode(" OR ", $filter);
			}

			$query['$or'] = $o;'function() { for (var key in this) { if (this[key] == "'.$filter.'") return true;} return false; }
			';
		}
		
		foreach ($where as $w) {
			$clause = preg_split("/([!=><]+|IS)/", $w, 3, PREG_SPLIT_DELIM_CAPTURE);
			$field = NULL;
			$value = NULL;
			if (is_scalar($clause[0]) && !is_numeric($clause[0]) && trim($clause[0])[0] != "'" && trim($clause[0])[0] != ":" && strtolower(trim($clause[0])) != "null") {
				$field = trim($clause[0]);
			} else {
				$value = trim($clause[0]);
			}
			if (is_scalar($clause[2]) && !is_numeric($clause[2]) && trim($clause[2])[0] != "'" && trim($clause[2])[0] != ":" && strtolower(trim($clause[2])) != "null") {
				$field = trim($clause[2]);
			} else {
				$value = trim($clause[2]);
			}
			if ($value[0] == "'" && $value[strlen($value)-1] == "'") {
				$value = substr($value, 1, strlen($value)-2);
			}
			if ($value[0] == ":")
				$value = $params[substr($value, 1)];
				
			if ($field == "_id") {
				$value = new MongoID($value);
			}
			
			switch($clause[1]) {
				case "="; $query[$field] = $value; break;
				case "!="; $query[$field] = array('$ne' => $value); break;
				case ">"; $query[$field] = array('$gt' => $value); break;
				case ">="; $query[$field] = array('$gte' => $value); break;
				case "<="; $query[$field] = array('$lte' => $value); break;
				case "<"; $query[$field] = array('$lt' => $value); break;
				case "IS"; $query[$field] = null; break;
				default: throw new Exception("Unknown operation $w");
			}
		}
		Logger::debug("Query", $query);
		return $query;
	}


	public function getQueryValues($fields, $tables, $joint, $where, $filter, $filter_fields, $order, $group, $params, $limit, $pos, $distinct) {
		Logger::debug("getQueryValues " . json_encode(array("fields"=>$fields, "tables"=>$tables, "joint"=>$joint, "where"=>$where, "order"=>$order, "group"=>$group, "params"=>$params, "limit"=>$limit, "pos"=>$pos, "distinct"=>$distinct)));
		$collection = $this->db->selectCollection($tables[0]);
		$_query = $this->createQuery($where, $filter, $filter_fields, $group, $params);
		$_fields = array();
		foreach ($fields as $field) {
			$_fields[$field] = true;
		}
		Logger::debug("getQueryValues " . json_encode(array("fields"=>$_fields, "query"=>$_query)));
		return new MongodbCursorHelper($collection->find($_query, $_fields), $fields);
	}


	public function getQueryValuesArray($fields, $tables, $joint, $where, $filter, $filter_fields, $order, $group, $params, $limit, $pos, $distinct) {
		$collection = array();
		$result = $this->getQueryValues($fields, $tables, $joint, $where, $filter, $filter_fields, $order, $group, $params, $limit, $pos, $distinct);
		foreach ($result as $row) {
			$collection[] = $row;
		}
		return $collection;
	}


	public function getQueryCount($tables, $joint, $where, $filter, $filter_fields, $group, $params, $distinct) {
		$collection = $this->db->selectCollection($tables[0]);
		$_query = $this->createQuery($where, $filter, $filter_fields, $group, $params);
		return $collection->count($_query);
	}


	public function updateModelField($name, $params) {
		if (!array_key_exists("type", $params) || $params["type"] == ModelField::TYPE_AUTO) {
			$params["type"] = ModelField::TYPE_TEXT;
		}
		if (array_key_exists("primary", $params) && $params["primary"] &&
			  array_key_exists("autoincrement", $params) && $params["autoincrement"]) {
			$name = "_id";
			$params["type"] = ModelField::TYPE_TEXT;
		}
		//list($name, $params) = parent::updateModelField($name, $params);
		return array($name, $params);
	}


	public function getBlob($id) {
		Logger::debug("getBlob($id)");
		$gridFS = $this->db->getGridFS();
		$file = $gridFS->get($id);
		if ($file)
			return $file->getBytes();
		
		return null;
	}


	public function setBlob($id, $value) {
		Logger::debug("setBlob($id)");
		$gridFS = $this->db->getGridFS();
		$metadata = array();
		if ($id !== null) {
			$gridFS->delete($id);
			$metadata['_id'] = $id;
		}
		
		if ($value === null)
			return null;
		
		return $gridFS->storeBytes($value, $metadata);
	}

}


class MongodbCursorHelper extends BddCursorHelper {
	protected $fields;

	public function __construct($cursor, $fields) {
		parent::__construct($cursor);
		$this->fields = $fields;
	}


 	public function current() {
		$data = $this->cursor->current();
		if ($data) {
			$data['_id'] = (string)$data['_id'];
		}
		if (count($this->fields) > 0) {
			$_data = $data;
			$data = array();
			foreach($this->fields as $k => $v) {
				if (is_int($k))
					$data[$v] = $_data[$v];
				else
					$data[$k] = $_data[$v];
			}
		}
		Logger::debug("fetch data", $data);
		return $data;
	}
}
