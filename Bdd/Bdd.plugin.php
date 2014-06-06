<?php
configure("DBNAME", 'sqlite:' . DATA_DIR . '/db.sqlite');
configure("DBLOGIN", '');
configure("DBPASSWORD", '');
configure("CRUD_LIMIT", 30);

class BddPlugin extends Plugins {
	const MODEL_DIR = "model";
	const BASE_MODEL_DIR = "model/base";

	protected function autoload($class_name) {
		if (parent::autoload($class_name))
			return True;

		$plugin = Plugins::find(self::MODEL_DIR . DIRECTORY_SEPARATOR . $class_name . '.class.php');
		if ($plugin !== NULL) {
			require_once($plugin);
			return True;
		}
		$plugin = Plugins::find(self::BASE_MODEL_DIR . DIRECTORY_SEPARATOR . $class_name . '.class.php');
		if ($plugin !== NULL) {
			require_once($plugin);
			return True;
		}

		return False;
	}


	public function install() {
		Cli::pln(" * Create base classes");
		Model::createClassesFromConfig(array());

		Cli::pln(" * Create database structure");
		$bdd = Bdd::getInstance();
		if (is_dir(BddPlugin::MODEL_DIR)) {
			if ($dh = opendir(BddPlugin::MODEL_DIR)) {
				while (($file = readdir($dh)) !== false) {
					if (substr($file, -15) == "Model.class.php" && substr($file, 0, 4) != "Base") {
						$class = substr($file, 0, -10);
						$model = new $class();
						$bdd->query($bdd->dropTable($model->getTableName()));
						$bdd->query($model->createTable());
					}
				}
				closedir($dh);
			}
		}
	}


	public function cli($cli) {
		$cli->addCommand("model:export", array("Model", "export"), "Export database model to sql statements");
		$cli->addCommand("model:import", array("Model", "import"), "Import database model to json format");
		$cli->addCommand("model:create:classes", array("Model", "createClassesFromConfig"), "Create php classes from json configuration");
		$cli->addCommand("crud:create", array("Crud", "create"), "Create php class from json configuration");
	}

}
