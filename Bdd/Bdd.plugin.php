<?php namespace DavBfr\CF;
/**
 * Copyright (C) 2013-2015 David PHAM-VAN
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

Options::set("DBNAME", 'sqlite:' . Options::get('DATA_DIR') . '/db.sqlite', "Database name");
Options::set("DBLOGIN", '', "Database Login");
Options::set("DBPASSWORD", '', "Database password");
Options::set("CRUD_LIMIT", 30, "Max number of lines in lists");
Options::set("DATABASE_ALTER", true, "Allow to alter the database schema");
Options::set("DATABASE_ALTER_CONFIRMATION", true, "Interactively confirm any table structure change");


class BddPlugin extends Plugins {
	const MODEL_DIR = "model";
	const BASE_MODEL_DIR = "model/base";


	/**
	 * @param string $class_name
	 * @return bool
	 */
	protected function autoload($class_name) {
		if (parent::autoload($class_name))
			return true;

		$class_name = $this->removeNamespace($class_name);
		$plugin = Plugins::find(self::MODEL_DIR . DIRECTORY_SEPARATOR . $class_name . '.class.php');
		if ($plugin !== null) {
			require_once($plugin);
			return true;
		}
		$plugin = Plugins::find(self::BASE_MODEL_DIR . DIRECTORY_SEPARATOR . $class_name . '.class.php');
		if ($plugin !== null) {
			require_once($plugin);
			return true;
		}

		return false;
	}


	/**
	 * @throws \Exception
	 */
	public function preupdate() {
		Cli::pinfo(" * Create base classes");
		Model::createClassesFromConfig();
		$bdd = Bdd::getInstance();

		$config = Config::getInstance();
		foreach ($config->get("model", array()) as $table => $columns) {
			$className = __NAMESPACE__ . "\\" . ucfirst($table) . "Model";
			/** @var Model $model */
			$model = new $className();
			if (!$bdd->tableExists($model->getTableName())) {
				Cli::pinfo(" * Create table " . $model->getTableName());
				$model->createTable();
			} else if (Options::get('DATABASE_ALTER')) {
				Cli::pinfo(" * Update table " . $model->getTableName());
				$model->alterTable();
			}
		}
	}


	/**
	 * @throws \Exception
	 */
	public function install() {
		Cli::pinfo(" * Create database structure");
		$bdd = Bdd::getInstance();
		if (is_dir(self::MODEL_DIR)) {
			if ($dh = opendir(self::MODEL_DIR)) {
				while (($file = readdir($dh)) !== false) {
					if (substr($file, -15) == "Model.class.php" && substr($file, 0, 4) != "Base") {
						$class = __NAMESPACE__ . "\\" . substr($file, 0, -10);
						/** @var Model $model */
						$model = new $class();
						$bdd->dropTable($model->getTableName());
						$model->createTable();
					}
				}
				closedir($dh);
			}
		}
	}


	/**
	 * @param Cli $cli
	 */
	public function cli($cli) {
		$cli->addCommand("model:export", array(__NAMESPACE__ . "\\Model", "export"), "Export database model to sql statements");
		$cli->addCommand("model:import", array(__NAMESPACE__ . "\\Model", "import"), "Import database model to json format");
		$cli->addCommand("model:create:classes", array(__NAMESPACE__ . "\\Model", "createClassesFromConfig"), "Create php classes from json configuration");
		$cli->addCommand("crud:create", array(__NAMESPACE__ . "\\Crud", "create"), "Create php class from json configuration");
		$cli->addCommand("bdd:export", array(__NAMESPACE__ . "\\Bdd", "export"), "Export the database to json file");
		$cli->addCommand("bdd:import", array(__NAMESPACE__ . "\\Bdd", "cliImport"), "Import the database from json file");
	}

}
