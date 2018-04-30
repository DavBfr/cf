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

class Bdd {
	private static $instance = null;

	private $helper;
	private $driver;


	/**
	 * Bdd constructor.
	 * @throws \Exception
	 */
	private function __construct() {
		$this->driver = substr(DBNAME, 0, strpos(DBNAME, ":"));
		$helper = __NAMESPACE__ . "\\" . ucFirst($this->driver) . "Helper";
		if (class_exists($helper, true)) {
			$this->helper = new $helper(DBNAME, DBLOGIN, DBPASSWORD);
		} else {
			$this->helper = new PDOHelper(DBNAME, DBLOGIN, DBPASSWORD);
		}
	}


	/**
	 * @return Bdd
	 * @throws \Exception
	 */
	public static function getInstance() {
		if (is_null(self::$instance)) {
			self::$instance = new self();
		}

		return self::$instance;
	}


	/**
	 * @throws \Exception
	 */
	public static function export() {
		Cli::enableHelp();
		Cli::pr("{");
		$first_model = true;
		foreach (Model::getModels() as $class) {
			if (!$first_model)
				Cli::pln(",");
			$first_model = false;
			Cli::pln(json_encode($class) . ":[");
			$class = __NAMESPACE__ . "\\$class";
			/** @var Model $model */
			$model = new $class();
			$first_data = true;
			/** @var ModelData $data */
			foreach ($model->simpleSelect() as $data) {
				if (!$first_data)
					Cli::pr(",");
				$first_data = false;
				Cli::pln(json_encode($data->getValues()));
			}
			Cli::pr("]");
		}
		Cli::pln("}");
	}


	/**
	 * @param array $data
	 * @throws \Exception
	 */
	public function import(array $data) {
		foreach ($data as $class => $rows) {
			Logger::warning("Import data for $class");
			$class = __NAMESPACE__ . "\\$class";
			/** @var Model $model */
			$model = new $class();
			foreach ($rows as $row_data) {
				$row = $model->newRow();
				$row->setValues($row_data);
				$row->save();
			}
		}
	}


	/**
	 * @param $args
	 * @throws \Exception
	 */
	public static function cliImport($args) {
		$files = Cli::getInputs("files", "file names to import");
		Cli::enableHelp();
		$bdd = self::getInstance();
		foreach ($files as $filename) {
			Cli::pinfo("Import $filename in database");
			$data = Input::jsonDecode(file_get_contents($filename));
			$bdd->import($data);
		}
	}


	/**
	 * @param string $field
	 * @return string
	 */
	public function quoteIdent($field) {
		return $this->helper->quoteIdent($field);
	}


	/**
	 * @param string $field
	 * @return string
	 */
	public function quote($field) {
		return $this->helper->quote($field);
	}


	/**
	 * @param string $format
	 * @param string $date
	 * @return string
	 */
	public function strftime($format, $date) {
		return $this->helper->strftime($format, $date);
	}


	/**
	 * @param string $type
	 * @param mixed $value
	 * @return mixed
	 */
	public function formatIn($type, $value) {
		return $this->helper->formatIn($type, $value);
	}


	/**
	 * @param string $type
	 * @param mixed $value
	 * @return mixed
	 */
	public function formatOut($type, $value) {
		return $this->helper->formatOut($type, $value);
	}


	/**
	 * @param $value
	 * @return mixed
	 */
	public function getBlob($value) {
		return $this->helper->getBlob($value);
	}


	/**
	 * @param $oldvalue
	 * @param $newvalue
	 * @return mixed
	 */
	public function setBlob($oldvalue, $newvalue) {
		return $this->helper->setBlob($oldvalue, $newvalue);
	}


	/**
	 * @param string $table
	 * @param array $fields
	 * @return int
	 * @throws \Exception
	 */
	public function insert($table, array $fields) {
		return $this->helper->insert($table, $fields);
	}


	/**
	 * @param string $table
	 * @param array $fields
	 * @param string $key
	 * @return bool
	 * @throws \Exception
	 */
	public function update($table, array $fields, $key) {
		return $this->helper->update($table, $fields, $key);
	}


	/**
	 * @param string $table
	 * @param string $key
	 * @param string $value
	 * @return bool
	 * @throws \Exception
	 */
	public function delete($table, $key, $value) {
		return $this->helper->delete($table, $key, $value);
	}


	/**
	 * @param string $name
	 * @return string
	 */
	public function dropTableQuery($name) {
		return $this->helper->dropTableQuery($name);
	}


	/**
	 * @return array
	 */
	protected function getParams() {
		return array();
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
	 * @param int $pos
	 * @param bool $distinct
	 * @return string
	 */
	public function getQueryString(array $fields, array $tables, array $joint, array $where, array $filter, array $filter_fields, array $order, array $group, array $params, $limit, $pos, $distinct) {
		return $this->helper->getQueryString($fields, $tables, $joint, $where, $filter, $filter_fields, $order, $group, $params, $limit, $pos, $distinct);
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
	 * @param int $pos
	 * @param bool $distinct
	 * @return BddCursorHelper
	 * @throws \Exception
	 */
	public function getQueryValues(array $fields, array $tables, array $joint, array $where, array $filter, array $filter_fields, array $order, array $group, array $params, $limit, $pos, $distinct) {
		return $this->helper->getQueryValues($fields, $tables, $joint, $where, $filter, $filter_fields, $order, $group, $params, $limit, $pos, $distinct);
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
	 * @param int $pos
	 * @param bool $distinct
	 * @return array
	 * @throws \Exception
	 */
	public function getQueryValuesArray(array $fields, array $tables, array $joint, array $where, array $filter, array $filter_fields, array $order, array $group, array $params, $limit, $pos, $distinct) {
		return $this->helper->getQueryValuesArray($fields, $tables, $joint, $where, $filter, $filter_fields, $order, $group, $params, $limit, $pos, $distinct);
	}


	/**
	 * @param array $tables
	 * @param array $joint
	 * @param array $where
	 * @param array $filter
	 * @param array $filter_fields
	 * @param array $group
	 * @param array $params
	 * @param bool $distinct
	 * @return int
	 * @throws \Exception
	 */
	public function getQueryCount(array $tables, array $joint, array $where, array $filter, array $filter_fields, array $group, array $params, $distinct) {
		return $this->helper->getQueryCount($tables, $joint, $where, $filter, $filter_fields, $group, $params, $distinct);
	}


	/**
	 * @param string $name
	 * @return bool
	 */
	public function tableExists($name) {
		return $this->helper->tableExists($name);
	}


	/**
	 * @param string $name
	 * @return bool
	 * @throws \Exception
	 */
	public function dropTable($name) {
		return $this->helper->dropTable($name);
	}


	/**
	 * @param string $name
	 * @param array $table_structure
	 * @return string
	 */
	public function createTableQuery($name, array $table_structure) {
		return $this->helper->createTableQuery($name, $table_structure);
	}


	/**
	 * @param string $name
	 * @param array $table_structure
	 * @return mixed
	 * @throws \Exception
	 */
	public function createTable($name, array $table_structure) {
		return $this->helper->createTable($name, $table_structure);
	}


	/**
	 * @return string[]
	 */
	public function getTables() {
		return $this->helper->getTables();
	}


	/**
	 * @param string $name
	 * @return array
	 */
	public function getTableInfo($name) {
		return $this->helper->getTableInfo($name);
	}


	/**
	 * @param string $name
	 * @return string
	 */
	public function updateTableName($name) {
		return $this->helper->updateTableName($name);
	}


	/**
	 * @param string $name
	 * @param array $params
	 * @return array
	 */
	public function updateModelField($name, array $params) {
		return $this->helper->updateModelField($name, $params);
	}


}
