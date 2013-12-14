#!/usr/bin/env php
<?php
if (!defined('STDIN') )
	die("Not running from CLI");


define("ROOT_DIR", getcwd());
require_once(dirname(__file__) . DIRECTORY_SEPARATOR . "common.php");


function arguments($argv) {
	$_ARG = array();
	foreach ($argv as $arg) {
		if (preg_match('#^-{1,2}([a-zA-Z0-9]*)=?(.*)$#', $arg, $matches)) {
			$key = $matches[1];
			switch ($matches[2])
			{
				case '':
				case 'true':
					$arg = true;
					break;
				case 'false':
					$arg = false;
					break;
				default:
					$arg = $matches[2];
			}
			$_ARG[$key] = $arg;
		} else {
			$_ARG['input'][] = $arg;
		}
	}
	return $_ARG;
}

function question() {
	echo "Are you sure you want to do this?  Type 'yes' to continue: ";
	$handle = fopen ("php://stdin","r");
	$line = fgets($handle);
	if(trim($line) != 'yes'){
		echo "ABORTING!\n";
		exit;
	}
	echo "\n";
	echo "Thank you, continuing...\n";
}

if (is_dir(MODEL_DIR)) {
	if ($dh = opendir(MODEL_DIR)) {
		while (($file = readdir($dh)) !== false) {
			if (substr($file, -15) == "Model.class.php") {
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
