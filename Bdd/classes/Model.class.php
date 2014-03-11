<?php

class Model {

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
		if (is_dir(MODEL_DIR)) {
			if ($dh = opendir(MODEL_DIR)) {
				while (($file = readdir($dh)) !== false) {
					if (substr($file, -15) == "Model.class.php") {
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
		if (is_dir(MODEL_DIR)) {
			foreach ($config->get("model", array()) as $table => $columns) {
				$className = ucfirst($table) . "Model";
				$filename = MODEL_DIR . "/" . ucfirst($table) . "Model.class.php";
				if (file_exists($filename))
					continue;

				Cli::pln($className);
				$f = fopen($filename, "w");
				fwrite($f, "<?php\n\nclass $className extends Model {\n\tconst TABLE = \"$table\";\n");
				foreach($columns as $name => $params) {
					fwrite($f, "\tconst ".strtoupper($name)." = \"$name\"; // " . $params["type"] . "\n");
				}
				fwrite($f, "\n\n}\n");
				fclose($f);
			}
		}
	}


	protected function getTable() {
		$table = get_class($this);
		if (substr($table, -5) == "Model") {
			return $this->getFromConfig("model." . strtolower(substr($table, 0, -5)));
		}
		return array($table, array());
	}


	public function getFromConfig($key) {
		$config = Config::getInstance();
		return array(substr($key, strrpos($key, ".") + 1), $config->get($key, array()));
	}


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
