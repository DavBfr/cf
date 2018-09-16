<?php namespace DavBfr\CF;

/**
 * Copyright (C) 2013-2018 David PHAM-VAN
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

use Exception;

abstract class Crud extends Rest {
	const ID = "CRUD_ID_FIELD";

	protected $options;
	/** @var Model $model */
	protected $model;
	protected $limit;


	/**
	 * @param array $r
	 * @throws Exception
	 */
	protected function preProcess($r) {
		parent::preProcess($r);
		$this->model = $this->getModel();
		$this->options = array_merge(self::defaultOptions(), $this->getOptions());
	}


	/**
	 * @return Model
	 */
	abstract protected function getModel();


	/**
	 * @return array
	 * @throws Exception
	 */
	private static function defaultOptions() {
		return array(
			"list_title" => Lang::get("core.list"),
			"detail_title" => Lang::get("core.form"),
			"new_title" => Lang::get("core.new_form"),
			"can_create" => true,
			"can_delete" => true,
			"can_view" => true,
			"can_filter" => true,
			"list_partial" => "crud-list.php",
			"detail_partial" => "crud-detail.php",
			"limit" => CRUD_LIMIT,
			"foreign_limit" => null,
		);
	}


	/**
	 * @return array
	 */
	protected function getOptions() {
		return array(
			"list_partial" => array($this->model->getTableName() . "-crud-list.php", "crud-list.php"),
			"detail_partial" => array($this->model->getTableName() . "-crud-detail.php", "crud-detail.php"),
			"table" => $this->model->getTableName(),
		);
	}


	public function getRoutes() {
		$this->addRoute("/", "GET", "get_list", array("description" => "Return elements in the list", "parameters" => array(array("name" => "q", "description" => "List filter", "schema" => array("type" => "string"), "in" => "query"), array("name" => "p", "description" => "Page number", "schema" => array("type" => "integer"), "in" => "query"))));
		$this->addRoute("/count", "GET", "get_count", array("description" => "Return the number of elements in the list", "parameters" => array(array("name" => "q", "description" => "List filter", "schema" => array("type" => "string"), "in" => "query"))));
		$this->addRoute("/list", "GET", "get_list_partial", array("description" => "Return the listing template"));
		$this->addRoute("/detail", "GET", "get_detail_partial", array("description" => "Return the editor template"));
		$this->addRoute("/new", "GET", "get_new", array("description" => "Return default data for a new entry"));
		$this->addRoute("/get/:id", "GET", "get_item", array("description" => "Return data for the entry {id}"));
		$this->addRoute("/:id", "DELETE", "delete_item", array("description" => "Delete the entry {id}"));
		$this->addRoute("/", "PUT", "new_item", array("description" => "Create a new entry"));
		$this->addRoute("/:id", "POST", "update_item", array("description" => "Update the entry {id}"));
		$this->addRoute("/foreign/:name", "GET", "get_foreign", array("description" => "Get the data for foreign {name}"));
	}


	/**
	 * @param string $name
	 * @param mixed $value
	 * @return mixed
	 */
	public function getOption($name, $value) {
		return $this->options[$name];
	}


	/**
	 * @param Collection $col
	 * @param ModelField $field
	 */
	protected function filterListField($col, $field) {
		if ($field->isForeign()) {
			$f = $field->getForeign();
			if (is_array($f) && count($f) == 3) {
				$fname = $field->getForeignTable();
				list($t, $k, $v) = $f;
				$col->leftJoin("$t as {$fname}", "{$fname}.$k = " . $field->getFullName());
				$col->selectAs("{$fname}.$v", $field->getName());
			} else {
				$col->selectAs($field->getFullName(), $field->getName());
			}
		} else {
			$col->selectAs($field->getFullName(), $field->getName());
		}
	}


	/**
	 * @param Collection $col
	 */
	protected function filterList($col) {
		/** @var ModelField $field */
		foreach ($this->getFields() as $field) {
			if ($field->inList()) {
				$this->filterListField($col, $field);
			}
		}

		if (Input::has("q") && strlen(Input::get("q")) > 0) {
			$col->filter(Input::get("q"));
		}
	}


	/**
	 * @param Collection $col
	 * @param ModelField $field
	 */
	protected function filterForeign($col, $field) {
	}


	/**
	 * @param array $r
	 * @throws Exception
	 */
	protected function get_foreign($r) {
		Input::ensureRequest($r, array("name"));
		$name = $r["name"];
		$field = $this->model->getField($name);
		if ($field == null)
			ErrorHandler::error(400);

		$bdd = Bdd::getInstance();

		$foreign = $field->getForeign();
		if (is_string($foreign)) {
			$conf = Config::getInstance();
			Output::success(array("list" => $conf->get($foreign)));
		}

		list($table, $key, $value) = $foreign;
		$foreignModel = Model::getModel($table);
		$col = Collection::Query($table)
			->SelectAs($bdd->quoteIdent($key), $bdd->quoteIdent('key'))
			->SelectAs($bdd->quoteIdent($value), $bdd->quoteIdent('val'))
			->orderBy($bdd->quoteIdent($value))
			->limit($this->options["foreign_limit"]);

		if (Input::has("q") && strlen(Input::get("q")) > 0) {
			$col->filter(Input::get("q"));
		}

		$this->filterForeign($col, $field);

		$list = array();
		foreach ($col->getValues(Input::has("p") ? intval(Input::get("p")) : 0) as $row) {
			if ($foreignModel)
				$list[] = array("key" => $foreignModel->getField($key)->formatOut($row['key']), "value" => $foreignModel->getField($value)->formatOut($row['val']));
			else
				$list[] = array("key" => $row['key'], "value" => $row['val']);
		}

		Output::success(array("list" => $list));
	}


	/**
	 * @param array $row
	 * @return array
	 */
	protected function list_values($row) {
		return $row;
	}


	/**
	 * @param array $r
	 * @throws Exception
	 */
	protected function get_list($r) {
		$col = Collection::Model($this->model)
			->SelectAs($this->model->getField($this->model->getPrimaryField())->getFullName(), self::ID)
			->limit($this->options["limit"]);
		$this->filterList($col);

		$list = array();
		foreach ($col->getValues(Input::has("p") ? intval(Input::get("p")) : 0) as $row) {
			$list[] = $this->list_values($row);
		}

		Output::success(array("list" => $list));
	}


	/**
	 * @return ModelField[]
	 */
	protected function getFields() {
		return $this->model->getFields();
	}


	/**
	 * @param array $r
	 * @throws Exception
	 */
	protected function get_list_partial($r) {
		$tpt = new Template(array_merge($this->options, array("model" => $this->getFields())));
		$tpt->outputCached($this->options["list_partial"]);
	}


	/**
	 * @param array $r
	 * @throws Exception
	 */
	protected function get_detail_partial($r) {
		$tpt = new Template(array_merge($this->options, array("model" => $this->getFields())));
		$tpt->outputCached($this->options["detail_partial"]);
	}


	/**
	 * @param array $r
	 * @throws Exception
	 */
	protected function get_count($r) {
		$col = Collection::Model($this->model);
		$this->filterList($col);
		$count = $col->getCount();
		Output::success(array(
			'count' => intVal($count),
			'limit' => $this->options["limit"],
			'pages' => ceil(intVal($count) / $this->options["limit"])
		));
	}


	/**
	 * @param string $item
	 * @return array
	 */
	protected function getForeigns($item) {
		$foreigns = array();
		/** @var ModelField $field */
		foreach ($this->getFields() as $name => $field) {
			if ($field->isForeign())
				$foreigns[] = $name;
		}
		return $foreigns;
	}


	/**
	 * @param ModelData $item
	 * @return array
	 */
	protected function getExtra($item) {
		return array();
	}


	/**
	 * @param array $r
	 * @throws Exception
	 */
	protected function get_item($r) {
		Input::ensureRequest($r, array("id"));
		$id = $r["id"];
		$item = $this->model->getById($id);
		$values = array();
		/** @var ModelField $field */
		foreach ($this->getFields() as $field) {
			if ($field->isEditable() && !$field->isBlob()) {
				$name = $field->getName();
				$values[$name] = $item->get($name);
			}
		}

		$foreigns = $this->getForeigns($item);

		Output::success(array(self::ID => $id, "foreigns" => $foreigns, "data" => $values, "extra" => $this->getExtra($item)));
	}


	/**
	 * @param array $r
	 * @throws Exception
	 */
	protected function get_new($r) {
		$item = $this->model->newRow();

		$foreigns = $this->getForeigns($item);

		Output::success(array(self::ID => null, "foreigns" => $foreigns, "data" => $item->getValues(), "extra" => $this->getExtra($item)));
	}


	/**
	 * @param array $r
	 * @throws Exception
	 */
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


	/**
	 * @param array $post
	 */
	protected function fixValues(& $post) {
	}


	/**
	 * @param array $r
	 * @throws Exception
	 */
	protected function new_item($r) {
		ErrorHandler::RaiseExceptionOnError();
		try {
			Logger::debug("Crud::new_item in table " . $this->model->getTableName());
			$post = $this->jsonpost();
			$this->fixValues($post);
			$item = $this->model->newRow();
			$item->setValues($post);
			$item->save();
			Output::success(array("id" => $item->getId()));
		} catch (Exception $e) {
			Output::error($e->getMessage());
		}
	}


	/**
	 * @param array $r
	 * @throws Exception
	 */
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
			Output::success(array("id" => $id));
		} catch (Exception $e) {
			Output::error($e->getMessage());
		}
	}


	/**
	 * @throws Exception
	 */
	public static function create() {
		$create_tpt = Cli::addSwitch("t", "Create templates");
		$models = Cli::getInputs("models", "Model names to create");
		Cli::enableHelp();

		$config = Config::getInstance();
		$rest = Plugins::get(Plugins::APP_NAME)->getDir() . DIRECTORY_SEPARATOR . self::REQUEST_DIR;
		System::ensureDir($rest);
		$ctrl = WWW_DIR . "/app/crud";
		System::ensureDir($ctrl);

		foreach ($models as $model) {
			$className = ucfirst($model) . "Rest";
			$modelClass = ucfirst($model) . "Model";
			Cli::pinfo(" * " . $className);
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
				$afields = $config->get("model." . $model);
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
