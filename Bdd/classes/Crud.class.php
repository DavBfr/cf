<?php
/**
 * Copyright (C) 2013-2014 David PHAM-VAN
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License
 * as published by the Free Software Foundation; version 2
 * of the License.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301, USA.
 **/

abstract class Crud extends Rest {
	const ID = "CRUD_ID_FIELD";

	protected $options;
	protected $model;
	protected $limit;


	function __construct() {
		parent::__construct();
		$this->options = array_merge(self::defaultOptions(), $this->getOptions());
		$this->model = $this->getModel();
		$this->limit = CRUD_LIMIT;
	}


	protected abstract function getModel();


	private static function defaultOptions() {
		return array(
			"list_title"=>Lang::get("core.list"),
			"detail_title"=>Lang::get("core.form"),
			"new_title"=>Lang::get("core.new_form"),
			"can_create"=>true,
			"can_delete"=>true,
			"can_view"=>true,
			"can_filter"=>true,
			"list_partial"=>"crud-list.php",
			"detail_partial"=>"crud-detail.php",
		);
	}

	protected function getOptions() {
		return array();
	}


	public function getRoutes() {
		$this->addRoute("/", "GET", "get_list");
		$this->addRoute("/count", "GET", "get_count");
		$this->addRoute("/list", "GET", "get_list_partial");
		$this->addRoute("/detail", "GET", "get_detail_partial");
		$this->addRoute("/:id", "GET", "get_item");
		$this->addRoute("/:id", "DELETE", "delete_item");
		$this->addRoute("/", "PUT", "new_item");
		$this->addRoute("/:id", "POST", "update_item");
		$this->addRoute("/foreign/:name", "GET", "get_foreign");
	}


	public function getOption($name, $value) {
		return $this->options[$name];
	}


	protected function filterList($col) {
		foreach ($this->model->getFields() as $field) {
			if ($field->inList()) {
				$col->select($field->getName());
			}
		}
	}


	protected function get_foreign($r) {
		Input::ensureRequest($r, array("name"));
		$name = $r["name"];
		$field = $this->model->getField($name);
		$bdd = Bdd::getInstance();

		list($table, $key, $value) = $field->getForeign();
		$col = Collection::Query($table)
		->SelectAs($bdd->quoteIdent($key), $bdd->quoteIdent('key'))
		->SelectAs($bdd->quoteIdent($value), $bdd->quoteIdent('val'))
		->limit($this->limit);
		
		if (isset($_GET["q"]) && strlen($_GET["q"])>0) {
			$col->filter("%".$_GET["q"]."%", "LIKE");
		}

		$list = [];
		foreach ($col->getValues(isset($_GET["p"])?intval($_GET["p"]):0) as $row) {
			$list[] = array("key"=>$row['key'], "value"=>$row['val']);
		}

		Output::success(array("list"=>$list));
	}


	protected function list_values($row) {
		return $row;
	}


	protected function get_list($r) {
		$col = Collection::Query($this->model->getTableName())
			->SelectAs($this->model->getPrimaryField(), self::ID)
			->limit($this->limit);
		$this->filterList($col);
		
		if (isset($_GET["q"]) && strlen($_GET["q"])>0) {
			$col->filter("%".$_GET["q"]."%", "LIKE");
		}
		
		$list = array();
		foreach($col->getValues(isset($_GET["p"])?intval($_GET["p"]):0) as $row) {
			$list[] = $this->list_values($row);
		}

		Output::success(array("list"=>$list));
	}


	protected function get_list_partial($r) {
		$tpt = new Template(array_merge($this->options, array("model" => $this->model->getFields())));
		$tpt->output($this->options["list_partial"]);
	}


	protected function get_detail_partial($r) {
		$tpt = new Template(array_merge($this->options, array("model" => $this->model->getFields())));
		$tpt->output($this->options["detail_partial"]);
	}


	protected function get_count($r) {
		$col = Collection::Query($this->model->getTableName());
		$this->filterList($col);
		if (isset($_GET["q"]) && strlen($_GET["q"])>0) {
			$col->filter("%".$_GET["q"]."%", "LIKE");
		}
		$col->resetSelect()->SelectAs("COUNT(".$this->model->getPrimaryField().")", "n");

		$reponse = $col->getValues();
		$count = $reponse->fetch(PDO::FETCH_NUM);
		if ($count)
			Output::success(array(
				'count'=>intVal($count[0]),
				'limit'=>$this->limit,
				'pages'=>ceil(intVal($count[0]) / $this->limit)
			));

		Output::error("No data");
	}


	protected function getForeigns($item) {
		$foreigns = array();
		foreach($this->model->getFields() as $name => $field) {
			if ($field->isForeign())
				$foreigns[] = $name;
		}
		return $foreigns;
	}


	protected function get_item($r) {
		Input::ensureRequest($r, array("id"));
		$id = $r["id"];
		$item = $this->model->getById($id);
		
		$foreigns = $this->getForeigns($item);
		
		Output::success(array(self::ID=>$id, "foreigns"=>$foreigns, "data"=>$item->getValues()));
	}


	protected function delete_item($r) {
		ErrorHandler::RaiseExceptionOnError();
		try {
			Input::ensureRequest($r, array("id"));
			$id = $r["id"];
			Logger::debug("Crud::delete_item id:" . $id . " in table " . $this->model->getTableName());
			$this->model->deleteById($id);
			Output::success();
		} catch (Exception $e) {
			Output::error($e->getMessage());
		}
	}


	protected function fixValues(& $post) {
	}


	protected function new_item($r) {
		ErrorHandler::RaiseExceptionOnError();
		try {
			Logger::debug("Crud::new_item in table " . $this->model->getTableName());
			$post = $this->jsonpost();
			$this->fixValues($post);
			$item = $this->model->newRow();
			$item->setValues($post);
			$item->save();
			Output::success(array("id"=>$item->getId()));
		} catch (Exception $e) {
			Output::error($e->getMessage());
		}
	}


	protected function update_item($r) {
		ErrorHandler::RaiseExceptionOnError();
		try {
			Input::ensureRequest($r, array("id"));
			$id = $r["id"];
			Logger::debug("Crud::update_item id:" . $id . " in table " . $this->model->getTableName());
			$post = $this->jsonpost();
			$this->fixValues($post);
			$item = $this->model->getById($id);
			$item->setValues($post);
			$item->save();
			Output::success(array("id"=>$id));
		} catch (Exception $e) {
			Output::error($e->getMessage());
		}
	}


	public static function create($args) {
		if (count($args["input"]) == 2) {
			Cli::pln("Missing model name to create");
			die();
		}
		
		$create_tpt = isset($args["t"]) && $args["t"] ? true : false;
		
		$config = Config::getInstance();
		$rest = Plugins::get(Plugins::APP_NAME)->getDir() . DIRECTORY_SEPARATOR . self::REQUEST_DIR;
		System::ensureDir($rest);
		$ctrl = WWW_DIR . "/app/crud";
		System::ensureDir($ctrl);

		foreach(array_slice($args["input"], 2) as $model) {
			$className = ucfirst($model) . "Rest";
			$modelClass = ucfirst($model) . "Model";;
			Cli::pln(" * " . $className);
			$filename = $rest . "/" . $className . ".class.php";
			$tpt = new Template(array(
				"className" => $className,
				"model" => $model,
				"umodel" => ucfirst($model),
				"modelClass" => $modelClass,
			));
			if (!file_exists($filename)) {
				$f = fopen($filename, "w");
				fwrite($f, $tpt->parse("crud-rest-skel.php"));
				fclose($f);
			}
			
			$filename = $ctrl . "/" . $className . ".js";
			if (!file_exists($filename)) {
				$f = fopen($filename, "w");
				fwrite($f, $tpt->parse("crud-app-skel.php"));
				fclose($f);
			}

			if ($create_tpt) {
				$templates = Plugins::get(Plugins::APP_NAME)->getDir() . DIRECTORY_SEPARATOR . Template::TEMPLATES_DIR;
				System::ensureDir($templates);
				$afields = $config->get("model." .     $model);
				$fields = array();
				foreach ($afields as $name => $prop) {
					$fields[$name] = new ModelField($model, $name, $prop);
				}
				$options = self::defaultOptions();
				$tpt = new Template(array_merge($options, array("model" => $fields)));
				
				$filename = $templates . "/" . $model . "-crud-list.php";
				if (!file_exists($filename)) {
					$f = fopen($filename, "w");
					fwrite($f, $tpt->parse($options["list_partial"]));
					fclose($f);
				}
				
				$filename = $templates . "/" . $model . "-crud-detail.php";
				if (!file_exists($filename)) {
					$f = fopen($filename, "w");
					fwrite($f, $tpt->parse($options["detail_partial"]));
					fclose($f);
				}
			}

		}
	}

}
