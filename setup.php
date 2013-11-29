<?php
require_once(dirname(__file__) . DIRECTORY_SEPARATOR . "common.php");
header("Content-Type: text/plain");

if (is_dir(MODEL_DIR)) {
	if ($dh = opendir(MODEL_DIR)) {
		while (($file = readdir($dh)) !== false) {
			if (substr($file, -10) == ".class.php") {
				$class = substr($file, 0, -10);
				$model = new $class();
				echo "DROP TABLE IF EXISTS `".$model->getTableName()."`;\n";
				echo $model->createTable();
				echo ";\n\n";
			}
		}
		closedir($dh);
	}
}

