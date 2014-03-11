<?php

abstract class Crud extends Rest {
	const ID = "CRUD_ID_FIELD";

	protected $model;
	protected $limit;


	function __construct() {
		parent::__construct();
		$this->model = $this->getModel();
		$this->limit = CRUD_LIMIT;
	}


	protected abstract function getModel();


	public function getRoutes() {
		$this->addRoute("/", "GET", "get_list");
		$this->addRoute("/count", "GET", "get_count");
		$this->addRoute("/list", "GET", "get_list_partial");
		$this->addRoute("/detail", "GET", "get_detail_partial");
		$this->addRoute("/:id", "GET", "get_item");
		$this->addRoute("/:id", "DELETE", "delete_item");
		$this->addRoute("/", "PUT", "new_item");
		$this->addRoute("/:id", "POST", "update_item");
	}


	public function build_query($fields, $tables, $where = array(1), $limit=NULL, $order=NULL) {
		$query = "SELECT ";
		$query .= implode(", ", $fields);
		$query .= " FROM ";
		$query .= implode(", ", $tables);
		$query .= " WHERE (";
		$query .= implode(") AND (", $where);
		$query .= ")";
		if ($order) {
			$query .= ' ORDER BY '. implode(", ", $order);
		}
		if ($limit) {
			$query .= ' LIMIT '. implode(", ", $limit);
		}
		return $query;
	}


	public function make_filter($fieldname, $value) {
		if ($this->fields[$fieldname]["type"] == "text")
			return $this->table.".$fieldname LIKE ".$this->bdd->quote("%".$value."%");
		return $this->table.".$fieldname = ".$this->bdd->quote($value);
	}


	public function make_global_filter($value) {
		$where = array();
		foreach($this->fields as $name => $prop) {
			if ($this->fields[$name]["type"] == "text")
				$where[] = $this->make_filter($name, $value);
		}
		return "(" . implode(") OR (", $where) . ")";
	}


	protected function filterList($col) {
		$col->select(array_keys($this->model->getFields()));
	}


	protected function get_list($r) {
		$col = Collection::Query($this->model->getTableName())
			->SelectAs($this->model->getPrimaryField(), self::ID)
			->limit(30);
		$this->filterList($col);
		Output::success(array("list"=>$col->getValuesArray(isset($_GET["p"])?intval($_GET["p"]):0)));
	}


	protected function get_list_partial($r) {
		$tpt = new Template(array("model" => $this->model->getFields()));
		$tpt->output("crud-list.php");
	}


	protected function get_detail_partial($r) {
		$tpt = new Template(array("model" => $this->model->getFields()));
		$tpt->output("crud-detail.php");
	}


	protected function get_count($r) {
		$col = Collection::Query($this->model->getTableName());
		$this->filterList($col);
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


	protected function get_item($r) {
		Input::ensureRequest($r, array("id"));
		$id = $r["id"];
		$item = $this->model->getById($id);
		Output::success(array(self::ID=>$id, "data"=>$item->getValues()));
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


	protected function new_item($r) {
		ErrorHandler::RaiseExceptionOnError();
		try {
			Logger::debug("Crud::new_item in table " . $this->model->getTableName());
			$post = $this->jsonpost();
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
			$item = $this->model->getById($id);
			$item->setValues($post);
			$item->save();
			Output::success(array("id"=>$id));
		} catch (Exception $e) {
			Output::error($e->getMessage());
		}
	}

}
