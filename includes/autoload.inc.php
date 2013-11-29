<?php

function __autoload($class_name) {
	if (!class_exists($class_name)) {
		
		$search = array();
		$search[] = CLASSES_DIR;
		$search[] = CF_CLASSES_DIR;
		$search[] = MODEL_DIR;

		foreach($search as $class_dir) {
			$class_file = $class_dir . DIRECTORY_SEPARATOR . $class_name . '.class.php';
			if (file_exists($class_file)) {
				require_once($class_file);
				return;
			}
		}

		send_error(404, NULL, "Class not found ${class_name} in " . __FILE__ . " line " . __LINE__);
	}
}
