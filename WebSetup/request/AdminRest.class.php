<?php namespace DavBfr\CF;

class AdminRest extends Crud {
	private $modelName;


	public function getRoutes() {
		$this->addRoute("/:model", "GET", "get_list");
		$this->addRoute("/:model/count", "GET", "get_count");
		$this->addRoute("/:model/list", "GET", "get_list_partial");
		$this->addRoute("/:model/detail", "GET", "get_detail_partial");
		$this->addRoute("/:model/new", "GET", "get_new");
		$this->addRoute("/:model/get/:id", "GET", "get_item");
		$this->addRoute("/:model/:id", "DELETE", "delete_item");
		$this->addRoute("/:model", "PUT", "new_item");
		$this->addRoute("/:model/:id", "POST", "update_item");
		$this->addRoute("/:model/foreign/:name", "GET", "get_foreign");
	}


	/**
	 * @return array
	 * @throws \Exception
	 */
	protected function getOptions() {
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


	protected function preProcess($r) {
		$this->modelName = __NAMESPACE__ . "\\" . ucfirst($r["model"]) . "Model";
		Logger::debug("Admin model:" . $this->modelName);
		parent::preProcess($r);
	}


	protected function getModel() {
		return new $this->modelName();
	}

	/**
	 * @return ModelField[]
	 */
	protected function getFields() {
		$fields = parent::getFields();
		foreach ($fields as &$field) {
			$field->setEditable(true);
			$field->setInList(true);
		}
		return $fields;
	}
}
