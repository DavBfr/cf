<?php
configure("DBNAME", 'sqlite:' . DATA_DIR . '/db.sqlite');
configure("DBLOGIN", '');
configure("DBPASSWORD", '');
configure("CRUD_LIMIT", 30);

class BddPlugin extends Plugins {
	const MODEL_DIR = "model";

	public function autoload($class_name) {
		if (parent::autoload($class_name))
			return True;
			
		$plugin = Plugins::find(self::MODEL_DIR . DIRECTORY_SEPARATOR . $class_name . '.class.php');
		if ($plugin !== NULL) {
			require_once($plugin);
			return True;
		}
		
		return False;
	}


	public function cli($cli) {
		$cli->addCommand("model:export", array("Model", "export"), "Export database model to sql statements");
		$cli->addCommand("model:import", array("Model", "import"), "Import database model to json format");
		$cli->addCommand("model:create:classes", array("Model", "createClassesFromConfig"), "Create php classes from json configuration");
		//$cli->addCommand("crud:create", array("Crud", "createClassesFromConfig"), "Create php classes from json configuration");
	}

}
