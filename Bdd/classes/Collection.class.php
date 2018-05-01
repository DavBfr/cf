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

class Collection {
	private $bdd;
	private $fields = array();
	private $tables = array();
	private $joint = array();
	private $where = array();
	private $filter = null;
	private $filter_fields = array();
	private $order = array();
	private $group = array();
	private $params = array();
	private $limit = null;
	private $distinct = false;
	/** @var Model $model */
	private $model = null;


	/**
	 * Collection constructor.
	 * @param Bdd $bdd
	 * @throws \Exception
	 */
	protected function __construct(Bdd $bdd = null) {
		if ($bdd === null)
			$this->bdd = Bdd::getInstance();
		else
			$this->bdd = $bdd;
	}


	/**
	 * @param string|array $from
	 * @param Bdd|null $bdd
	 * @return Collection
	 * @throws \Exception
	 */
	public static function Query($from = null, Bdd $bdd = null) {
		$c = new self($bdd);
		if ($from !== null)
			$c->from($from);
		return $c;
	}


	/**
	 * @param Model $model
	 * @param Bdd|null $bdd
	 * @return Collection
	 * @throws \Exception
	 */
	public static function Model(Model $model, Bdd $bdd = null) {
		$c = new self($bdd);
		$c->model = $model;
		$c->from($c->bdd->quoteIdent($model->getTableName()));
		return $c;
	}


	/**
	 * @param int $limit
	 * @return $this
	 */
	public function limit($limit) {
		$this->limit = $limit;
		return $this;
	}


	/**
	 * @return $this
	 */
	public function distinct() {
		$this->distinct = true;
		return $this;
	}


	/**
	 * @return $this
	 */
	public function select() {
		$args = func_get_args();
		if (count($args) == 1 && is_array($args[0]))
			$args = $args[0];

		$this->fields = array_merge($this->fields, $args);
		return $this;
	}


	/**
	 * @return $this
	 */
	public function resetSelect() {
		$this->fields = array();
		return $this;
	}


	/**
	 * @param string $field
	 * @param string $name
	 * @return $this
	 */
	public function selectAs($field, $name) {
		$this->fields[$name] = $field;
		return $this;
	}


	/**
	 * @return $this
	 */
	public function unSelect() {
		$args = func_get_args();
		if (count($args) == 1 && is_array($args[0]))
			$args = $args[0];

		foreach ($args as $field) {
			unset($this->fields[$field]);
		}
		return $this;
	}


	/**
	 * @return $this
	 */
	public function from() {
		$args = func_get_args();
		if (count($args) == 1 && is_array($args[0]))
			$args = $args[0];

		$this->tables = array_merge($this->tables, $args);
		return $this;
	}


	/**
	 * @param string $table
	 * @param string $filter
	 * @return $this
	 */
	public function leftJoin($table, $filter) {
		$this->joint[] = array($table, $filter);
		return $this;
	}


	/**
	 * @return $this
	 */
	public function where() {
		$args = func_get_args();
		if (count($args) == 1 && is_array($args[0]))
			$args = $args[0];

		$this->where = array_merge($this->where, $args);
		return $this;
	}


	/**
	 * @param string $name
	 * @param mixed $value
	 * @return $this
	 * @throws \Exception
	 */
	public function whereEq($name, $value) {
		$bdd = Bdd::getInstance();

		if (is_string($value))
			$this->where[] = $name . "=" . $bdd->quote($value);
		elseif ($value === null)
			$this->where[] = $name . " IS NULL";
		elseif ($value === true)
			$this->where[] = $name . "=1";
		elseif ($value === false)
			$this->where[] = $name . "=0";
		else
			$this->where[] = $name . "=" . $value;

		return $this;
	}


	/**
	 * @param mixed $value
	 * @param array|null $fields
	 */
	public function filter($value, array $fields = null) {
		$this->filter = $value;
		$this->filter_fields = $fields;
	}


	/**
	 * @return $this
	 */
	public function groupBy() {
		$args = func_get_args();
		if (count($args) == 1 && is_array($args[0]))
			$args = $args[0];

		$this->group = array_merge($this->group, $args);
		return $this;
	}


	/**
	 * @return $this
	 */
	public function orderBy() {
		$args = func_get_args();
		if (count($args) == 1 && is_array($args[0]))
			$args = $args[0];

		$this->order = array_merge($this->order, $args);
		return $this;
	}


	/**
	 * @return $this
	 */
	public function orderByDesc() {
		$args = func_get_args();
		if (count($args) == 1 && is_array($args[0]))
			$args = $args[0];

		$this->order = array_merge($this->order, array_map(function ($arg) {
			return "$arg desc";
		}, $args));
		return $this;
	}


	/**
	 * @param array $params
	 * @return $this
	 */
	public function with(array $params) {
		$this->params = array_merge($this->params, $params);
		return $this;
	}


	/**
	 * @param string $param
	 * @param string $value
	 * @return $this
	 */
	public function withValue($param, $value) {
		$this->params[$param] = $value;
		return $this;
	}


	/**
	 * @param string $format
	 * @param string $date
	 * @return string
	 */
	public function strftime($format, $date) {
		return $this->bdd->strftime($format, $date);
	}


	/**
	 * @param int $pos
	 * @return string
	 */
	public function getQueryString($pos = 0) {
		$filter_fields = $this->filter_fields === null ? $this->fields : $this->filter_fields;
		return $this->bdd->getQueryString($this->fields, $this->tables, $this->joint, $this->where, $this->filter, $filter_fields, $this->order, $this->group, $this->params, $this->limit, $pos, $this->distinct);
	}


	/**
	 * @param int $pos
	 * @return BddCursorHelper
	 * @throws \Exception
	 */
	public function getValues($pos = 0) {
		$filter_fields = $this->filter_fields === null ? $this->fields : $this->filter_fields;
		return $this->bdd->getQueryValues($this->fields, $this->tables, $this->joint, $this->where, $this->filter, $filter_fields, $this->order, $this->group, $this->params, $this->limit, $pos, $this->distinct);
	}


	/**
	 * @param int $pos
	 * @return array
	 * @throws \Exception
	 */
	public function getValuesArray($pos = 0) {
		$filter_fields = $this->filter_fields === null ? $this->fields : $this->filter_fields;
		return $this->bdd->getQueryValuesArray($this->fields, $this->tables, $this->joint, $this->where, $this->filter, $filter_fields, $this->order, $this->group, $this->params, $this->limit, $pos, $this->distinct);
	}


	/**
	 * @return int
	 * @throws \Exception
	 */
	public function getCount() {
		$filter_fields = $this->filter_fields === null ? $this->fields : $this->filter_fields;
		return $this->bdd->getQueryCount($this->tables, $this->joint, $this->where, $this->filter, $filter_fields, $this->group, $this->params, $this->distinct);
	}


	/**
	 * @param int $pos
	 * @return ModelData
	 * @throws \Exception
	 */
	public function modelData($pos = 0) {
		$md = $this->model->modelData();
		return new $md($this->model, $this->getValues($pos));
	}

}
