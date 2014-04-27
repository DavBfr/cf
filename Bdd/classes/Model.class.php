<?php

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
		if (class_exists($md) && is_subclass_of($md, ModelData))
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
						Cli::pln($bdd->dropTable($model->getTableName()) . ";");
						Cli::pln($model->createTable() . ";");
						Cli::pln("");
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
		Cli::pln(json_encode($tables));
	}


	public static function createClassesFromConfig($args) {
		$config = Config::getInstance();
		if (is_dir(BddPlugin::MODEL_DIR)) {
			foreach ($config->get("model", array()) as $table => $columns) {
				$baseClassName = "Base" . ucfirst($table) . "Model";
				$filename = BddPlugin::MODEL_DIR . "/" . $baseClassName . ".class.php";
				Cli::pln($baseClassName);
				$f = fopen($filename, "w");
				fwrite($f, "<?php\n\nclass $baseClassName extends Model {\n\tconst TABLE = " . ArrayWriter::quote($table) . ";\n");
				$colstr = ArrayWriter::toString($columns, 4);
				foreach($columns as $name => $params) {
					fwrite($f, "\tconst ".strtoupper($name)." = " . ArrayWriter::quote($name) . "; // " . $params["type"] . "\n");
					$colstr = str_replace(ArrayWriter::quote($name), "self::" . strtoupper($name), $colstr);
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
				fwrite($f, "<?php\n\nclass $className extends $baseClassName {\n\tconst TABLE = \"$table\";\n");
				fwrite($f, "\n\n}\n");
				fclose($f);
			}
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

}