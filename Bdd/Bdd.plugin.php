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
			
		foreach(Plugins::get_plugins() as $plugin) {
			$dir = Plugins::get($plugin)->getDir();
			$class_file = $dir . DIRECTORY_SEPARATOR . self::MODEL_DIR . DIRECTORY_SEPARATOR . $class_name . '.class.php';
			if (file_exists($class_file)) {
				require_once($class_file);
				return True;
			}
		}
		
		return False;
	}
}
