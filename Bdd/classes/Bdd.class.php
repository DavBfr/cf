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

class Bdd {
	private static $instance = null;

	private $helper;
	private $driver;


	private function __construct() {
		$this->driver = substr(DBNAME, 0, strpos(DBNAME, ":"));
		$helper = __NAMESPACE__ . "\\" . ucFirst($this->driver) . "Helper";
		if (class_exists($helper, true)) {
			$this->helper = new $helper(DBNAME, DBLOGIN, DBPASSWORD);
		} else {
			$this->helper = new PDOHelper(DBNAME, DBLOGIN, DBPASSWORD);
		}
	}


	public static function getInstance() {
		if (is_null(self::$instance)) {
			self::$instance = new self();
		}

		return self::$instance;
	}


	public function __call($name, $arguments) {
		return call_user_func_array(array($this->helper, $name), $arguments);
	}


	public static function export() {
		Cli::enableHelp();
		$bdd = self::getInstance();
		Cli::pr("{");
		$first_model = true;
		foreach (Model::getModels() as $class) {
			if (!$first_model)
				Cli::pln(",");
			$first_model = false;
			Cli::pln(json_encode($class) . ":[");
			$class = __NAMESPACE__ . "\\$class";
			$model = new $class();
			$first_data = true;
			foreach ($model->simpleSelect() as $data) {
				if (!$first_data)
					Cli::pr(",");
				$first_data = false;
				Cli::pln(json_encode($data->getValues()));
			}
			Cli::pr("]");
		}
		Cli::pln("}");
	}


	public function import($data) {
		foreach($data as $class => $rows) {
			Logger::warning("Import data for $class");
			$class = __NAMESPACE__ . "\\$class";
			$model = new $class();
			foreach($rows as $row_data) {
				$row = $model->newRow();
				$row->setValues($row_data);
				$row->save();
			}
		}
	}


	public static function cliImport($args) {
		$files = Cli::getInputs("files", "file names to import");
		Cli::enableHelp();
		$bdd = self::getInstance();
		foreach($files as $filename) {
			Cli::pinfo("Import $filename in database");
			$data = json_decode(file_get_contents($filename));
			$bdd->import($data);
		}
	}


}
