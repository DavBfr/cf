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

use Exception;

abstract class Model {

	protected $table;
	protected $fields;
	protected $modelData;


	/**
	 * Model constructor.
	 */
	public function __construct() {
		$this->modelData = $this->getModelData();
		list($this->table, $fields) = $this->getTable();
		$this->fields = array();
		foreach ($fields as $name => $prop) {
			$this->fields[$name] = new ModelField($this->table, $name, $prop);
		}
	}


	/**
	 * @param string $name
	 * @return Model
	 */
	public static function getModel($name) {
		$md = __NAMESPACE__ . "\\" . ucfirst($name) . "Model";
		if (class_exists($md) && is_subclass_of($md, __NAMESPACE__ . "\\Model"))
			return new $md;

		return null;
	}


	/**
	 * @return string
	 */
	protected function getModelData() {
		$md = get_class($this) . "Data";
		if (class_exists($md) && is_subclass_of($md, __NAMESPACE__ . "\\ModelData"))
			return get_class($this) . "Data";

		return __NAMESPACE__ . "\\ModelData";
	}


	/**
	 * @return string
	 */
	public function modelData() {
		return $this->modelData;
	}


	/**
	 * @return string[]
	 */
	public static function getModels() {
		$list = array();
		$config = Config::getInstance();
		foreach ($config->get("model", array()) as $table => $columns) {
			$list[] = ucfirst($table) . "Model";
		}
		return $list;
	}


	/**
	 * @throws Exception
	 */
	public static function export() {
		Cli::enableHelp();
		$bdd = Bdd::getInstance();
		foreach (self::getModels() as $class) {
			$class = __NAMESPACE__ . "\\$class";
			/** @var Model $model */
			$model = new $class();
			$drop = $bdd->dropTableQuery($model->getTableName());
			$create = $bdd->createTableQuery($model->table, $model->fields);
			if ($create) {
				if ($drop)
					Cli::pln($drop . ";");
				Cli::pln($create . ";");
				Cli::pln();
			}
		}
	}


	/**
	 * @throws Exception
	 */
	public static function import() {
		Cli::enableHelp();
		Cli::pr("\"model\": ");
		$tables = array();
		$bdd = Bdd::getInstance();
		foreach ($bdd->getTables() as $table) {
			$tables[$table] = $bdd->getTableInfo($table);
		}
		$p = 0;
		if (version_compare(PHP_VERSION, '5.4.0') >= 0)
			$p = JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE;

		Cli::pln(json_encode($tables, $p));
	}


	/**
	 * @throws Exception
	 */
	public static function createClassesFromConfig() {
		$bdd = Bdd::getInstance();
		$config = Config::getInstance();
		if (!is_dir(BddPlugin::MODEL_DIR)) {
			@mkdir(BddPlugin::MODEL_DIR, 0744, true);
		}
		if (!is_dir(BddPlugin::BASE_MODEL_DIR)) {
			@mkdir(BddPlugin::BASE_MODEL_DIR, 0744, true);
		}

		foreach ($config->get("model", array()) as $table => $columns) {
			$baseClassName = "Base" . ucfirst($table) . "Model";
			$filename = BddPlugin::BASE_MODEL_DIR . "/" . $baseClassName . ".class.php";
			Cli::pinfo("   * $baseClassName");
			$f = fopen($filename, "w");
			$tn = $bdd->updateTableName($table);
			fwrite($f, "<?php namespace " . __NAMESPACE__ . ";\n\nabstract class $baseClassName extends Model {\n\tconst TABLE = " . ArrayWriter::quote($tn) . ";\n");
			$new_columns = array();
			$new_names = array();
			foreach ($columns as $name => $params) {
				if (substr($name, 0, 2) == "__" && substr($name, strlen($name) - 2) == "__")
					continue;
				list($_name, $params) = $bdd->updateModelField($name, $params);
				$new_columns[$_name] = $params;
				$new_names[$_name] = $name;
			}
			$colstr = ArrayWriter::toString($new_columns, 4);
			foreach ($new_columns as $name => $params) {
				fwrite($f, "\tconst " . strtoupper($new_names[$name]) . " = " . ArrayWriter::quote($name) . "; // " . (array_key_exists("type", $params) ? $params["type"] : ModelField::TYPE_AUTO) . "\n");
				fwrite($f, "\tconst f_" . strtoupper($new_names[$name]) . " = " . ArrayWriter::quote($bdd->quoteIdent($tn) . "." . $bdd->quoteIdent($name)) . ";\n");
			}

			fwrite($f, "\n\n\tprotected function getTable() {\n");
			fwrite($f, "\t\treturn array(\n");
			fwrite($f, "\t\t\tself::TABLE,\n");
			fwrite($f, "\t\t\t" . $colstr . ",\n");
			fwrite($f, "\t\t);\n\t}\n");
			fwrite($f, "\n}\n");
			fclose($f);

			$className = ucfirst($table) . "Model";
			$filename = BddPlugin::MODEL_DIR . "/" . $className . ".class.php";
			if (Plugins::find($filename))
				continue;

			if (array_key_exists("__baseClassName__", $columns)) {
				$baseClassName = $columns["__baseClassName__"];
			}

			Cli::pinfo("   * $className");
			$f = fopen($filename, "w");
			fwrite($f, "<?php namespace " . __NAMESPACE__ . ";\n\nclass $className extends $baseClassName {\n\n}\n");
			fclose($f);
		}
	}


	/**
	 * @return string
	 */
	abstract protected function getTable();


	/**
	 * @return string
	 */
	public function getTableName() {
		return $this->table;
	}


	/**
	 * @return ModelField[]
	 */
	public function getFields() {
		return $this->fields;
	}


	/**
	 * @param string $name
	 * @return ModelField
	 */
	public function getField($name) {
		return $this->fields[$name];
	}


	/**
	 * @return string
	 * @throws Exception
	 */
	public function getPrimaryField() {
		foreach ($this->fields as $field) {
			if ($field->isPrimary()) {
				return $field->getName();
			}
		}

		throw new Exception("No Primary Key found");
	}


	/**
	 * @return mixed
	 * @throws Exception
	 */
	public function createTable() {
		$bdd = Bdd::getInstance();
		return $bdd->createTable($this->table, $this->fields);
	}


	/**
	 * @return ModelData
	 */
	public function newRow() {
		return new $this->modelData($this);
	}


	/**
	 * @param array $where
	 * @param array $params
	 * @return ModelData
	 * @throws Exception
	 */
	public function simpleSelect($where = array(), $params = array()) {
		$bdd = Bdd::getInstance();
		return Collection::Model($this)
			->select(array_map(array($bdd, "quoteIdent"), array_keys($this->fields)))
			->where($where)
			->with($params)
			->modelData();
	}


	/**
	 * @param $id
	 * @return ModelData
	 * @throws Exception
	 */
	public function getById($id) {
		return $this->getBy($this->getPrimaryField(), $id);
	}


	/**
	 * @param $id
	 * @throws Exception
	 */
	public function deleteById($id) {
		$bdd = Bdd::getInstance();
		$data = $this->getById($id);
		if ($data->isEmpty())
			return;

		foreach ($this->fields as $name => $field) {
			if ($field->isBlob()) {
				$data->setBlob($name, null);
			}
		}
		$bdd->delete($this->getTableName(), $this->getPrimaryField(), $id);
		$this->dataChanged();
	}


	public function dataChanged() {
	}


	/**
	 * @param string $field
	 * @param mixed $value
	 * @return ModelData
	 * @throws Exception
	 */
	public function getBy($field, $value) {
		$bdd = Bdd::getInstance();
		if (array_key_exists($field, $this->fields)) {
			return $this->simpleSelect(array($bdd->quoteIdent($field) . "=:value"), array("value" => $value));
		}

		throw new Exception("Field $field not found");
	}


	/**
	 * @param string $field
	 * @return Model
	 */
	public function getForeign($field) {
		if (array_key_exists($field, $this->fields)) {
			/** @noinspection PhpUnusedLocalVariableInspection */
			list($table, $key, $value) = $this->fields[$field]->getForeign();
			$className = __NAMESPACE__ . "\\" . ucfirst($table) . "Model";
			return new $className;
		}

		return null;
	}

}
