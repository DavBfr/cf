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

abstract class Model {

	protected $table;
	protected $fields;
	protected $modelData;


	public function __construct() {
		$this->modelData = $this->getModelData();
		list($this->table, $fields) = $this->getTable();
		$this->fields = array();
		foreach ($fields as $name => $prop) {
			$this->fields[$name] = new ModelField($this->table, $name, $prop);
		}
	}


	protected function getModelData() {
		$md = get_class($this)."Data";
		if (class_exists($md) && is_subclass_of($md, "ModelData"))
			return get_class($this)."Data";

		return "ModelData";
	}


	public static function export($args) {
		$bdd = Bdd::getInstance();
		if (is_dir(BddPlugin::MODEL_DIR)) {
			if ($dh = opendir(BddPlugin::MODEL_DIR)) {
				while (($file = readdir($dh)) !== false) {
					if (substr($file, -15) == "Model.class.php" && substr($file, 0, 4) != "Base") {
						$class = substr($file, 0, -10);
						$model = new $class();
						$drop = $bdd->dropTableQuery($model->getTableName());
						$create = $bdd->createTableQuery($model->table, $model->fields);
						if ($create) {
							if ($drop)
								Cli::pln($drop . ";");
							Cli::pln($create . ";");
							Cli::pln("");
						}
					}
				}
				closedir($dh);
			}
		}
	}


	public static function import($args) {
		Cli::pr("\"model\": ");
		$tables = array();
		$bdd = Bdd::getInstance();
		foreach($bdd->getTables() as $table) {
			$tables[$table] = $bdd->getTableInfo($table);
		}
		$p = 0;
		if (version_compare(PHP_VERSION, '5.4.0') >= 0)
			$p = JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES|JSON_UNESCAPED_UNICODE;

		Cli::pln(json_encode($tables, $p));
	}


	public static function createClassesFromConfig($args) {
		$bdd = Bdd::getInstance();
		$config = Config::getInstance();
		if (! is_dir(BddPlugin::MODEL_DIR)) {
			mkdir(BddPlugin::MODEL_DIR, 0744, true);
		}
		if (! is_dir(BddPlugin::BASE_MODEL_DIR)) {
			mkdir(BddPlugin::BASE_MODEL_DIR, 0744, true);
		}

		foreach ($config->get("model", array()) as $table => $columns) {
			$baseClassName = "Base" . ucfirst($table) . "Model";
			$filename = BddPlugin::BASE_MODEL_DIR . "/" . $baseClassName . ".class.php";
			Cli::pln("     $baseClassName");
			$f = fopen($filename, "w");
			fwrite($f, "<?php\n\nabstract class $baseClassName extends Model {\n\tconst TABLE = " . ArrayWriter::quote($table) . ";\n");
			$new_columns = array();
			$new_names = array();
			foreach($columns as $name => $params) {
				list($_name, $params) = $bdd->updateModelField($name, $params);
				$new_columns[$_name] = $params;
				$new_names[$_name] = $name;
			}
			$colstr = ArrayWriter::toString($new_columns, 4);
			foreach($new_columns as $name => $params) {
				fwrite($f, "\tconst ".strtoupper($new_names[$name])." = " . ArrayWriter::quote($name) . "; // " . (array_key_exists("type", $params) ? $params["type"] : ModelField::TYPE_AUTO) . "\n");
				$colstr = str_replace(ArrayWriter::quote($name), "self::" . strtoupper($new_names[$name]), $colstr);
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
			if (file_exists($filename))
				continue;

			Cli::pln($className);
			$f = fopen($filename, "w");
			fwrite($f, "<?php\n\nclass $className extends $baseClassName {\n\n}\n");
			fclose($f);
		}
	}


	abstract protected function getTable();


	public function getTableName() {
		return $this->table;
	}


	public function getFields() {
		return $this->fields;
	}


	public function getField($name) {
		return $this->fields[$name];
	}


	public function getPrimaryField() {
		foreach($this->fields as $field) {
			if ($field->isPrimary()) {
				return $field->getName();
			}
		}

		throw new Exception("No Primary Key found");
	}


	public function createTable() {
		$bdd = Bdd::getInstance();
		return $bdd->createTable($this->table, $this->fields);
	}


	public function newRow() {
		return new $this->modelData($this);
	}


	public function simpleSelect($where=array(), $params=array()) {
		$bdd = Bdd::getInstance();
		$this->simpleselect = Collection::Query()
			->select(array_map(array($bdd, "quoteIdent"), array_keys($this->fields)))
			->from($bdd->quoteIdent($this->table))
			->where($where)
			->with($params)
			->getValues();
		return new $this->modelData($this, $this->simpleselect);
	}


	public function getById($id) {
		return $this->getBy($this->getPrimaryField(), $id);
	}


	public function deleteById($id) {
		$bdd = Bdd::getInstance();
		$bdd->delete($this->getTableName(), $this->getPrimaryField(), $id);
		$this->dataChanged();
	}


	public function dataChanged() {
	}


	public function getBy($field, $value) {
		$bdd = Bdd::getInstance();
		if (array_key_exists($field, $this->fields)) {
			return $this->simpleSelect(array($bdd->quoteIdent($field) . "=:value"), array("value"=>$value));
		}

		throw new Exception("Field $field not found");
	}


	public function getForeign($field) {
		if (array_key_exists($field, $this->fields)) {
			list($table, $key, $value) = $this->fields[$field]->getForeign();
			return new $table;
		}
		
		return NULL;
	}

}
