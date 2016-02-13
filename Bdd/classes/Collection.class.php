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

class Collection {
	private $bdd;
	private $fields;
	private $tables;
	private $joint;
	private $where;
	private $filter;
	private $filter_fields;
	private $order;
	private $group;
	private $params;
	private $limit;
	private $distinct;
	private $model;


	protected function __construct($bdd) {
		if ($bdd === NULL)
			$this->bdd = Bdd::getInstance();
		else
		$this->bdd = $bdd;

		$this->fields = array();
		$this->tables = array();
		$this->joint = array();
		$this->where = array();
		$this->filter = NULL;
		$this->filter_fields = NULL;
		$this->order = array();
		$this->group = array();
		$this->params = array();
		$this->limit = NULL;
		$this->distinct = false;
		$this->model = NULL;
	}


	public static function Query($from=NULL, $bdd=NULL) {
		$c = new self($bdd);
		if ($from !== NULL)
		  $c->from($from);
		return $c;
	}


	public static function Model($model, $bdd=NULL) {
		$c = new self($bdd);
		$c->model = $model;
		$c->from($c->bdd->quoteIdent($model->getTableName()));
		return $c;
	}


	public function limit($limit) {
		$this->limit = $limit;
		return $this;
	}


	public function distinct() {
		$this->distinct = true;
		return $this;
	}


	public function select() {
		$args = func_get_args();
		if (count($args) == 1 && is_array($args[0]))
			$args = $args[0];

		$this->fields = array_merge($this->fields, $args);
		return $this;
	}


	public function resetSelect() {
		$this->fields = array();
		return $this;
	}


	public function selectAs($field, $name) {
		$this->fields[$name] = $field;
		return $this;
	}


	public function unSelect($field, $name) {
		$args = func_get_args();
		if (count($args) == 1 && is_array($args[0]))
			$args = $args[0];

		foreach($args as $field) {
			unset($this->fields[$field]);
		}
		return $this;
	}


	public function from() {
		$args = func_get_args();
		if (count($args) == 1 && is_array($args[0]))
			$args = $args[0];

		$this->tables = array_merge($this->tables, $args);
		return $this;
	}


	public function leftJoin($table, $filter) {
		$this->joint[] = array($table, $filter);
		return $this;
	}

	public function where() {
		$args = func_get_args();
		if (count($args) == 1 && is_array($args[0]))
			$args = $args[0];

		$this->where = array_merge($this->where, $args);
		return $this;
	}


	public function whereEq($name, $value) {
		$bdd = Bdd::getInstance();

		if (is_string($value))
			$this->where[] = $name . "=" . $bdd->quote($value);
		elseif ($value === NULL)
			$this->where[] = $name . " IS NULL";
		elseif ($value === true)
			$this->where[] = $name . "=1";
		elseif ($value === false)
			$this->where[] = $name . "=0";
		else
			$this->where[] = $name . "=" . $value;

		return $this;
	}


	public function filter($value, $fields = NULL) {
		$this->filter = $value;
		$this->filter_fields = $fields;
	}


	public function groupBy() {
		$args = func_get_args();
		if (count($args) == 1 && is_array($args[0]))
			$args = $args[0];

		$this->group = array_merge($this->group, $args);
		return $this;
	}


	public function orderBy() {
		$args = func_get_args();
		if (count($args) == 1 && is_array($args[0]))
			$args = $args[0];

		$this->order = array_merge($this->order, $args);
		return $this;
	}


	public function orderByDesc() {
		$args = func_get_args();
		if (count($args) == 1 && is_array($args[0]))
			$args = $args[0];

		$this->order = array_merge($this->order, array_map(function ($arg) { return "$arg desc"; }, $args));
		return $this;
	}


	public function with($params) {
		$this->params = array_merge($this->params, $params);
		return $this;
	}


	public function withValue($param, $value) {
		$this->params[$param] = $value;
		return $this;
	}

	public function strftime($format, $date) {
		return $this->bdd->strftime($format, $date);
	}


	public function getQueryString($pos = 0) {
		$filter_fields = $this->filter_fields === NULL ? $this->fields : $this->filter_fields;
		return $this->bdd->getQueryString($this->fields, $this->tables, $this->joint, $this->where, $this->filter, $filter_fields, $this->order, $this->group, $this->params, $this->limit, $pos, $this->distinct);
	}


	public function getValues($pos = 0) {
		$filter_fields = $this->filter_fields === NULL ? $this->fields : $this->filter_fields;
		return $this->bdd->getQueryValues($this->fields, $this->tables, $this->joint, $this->where, $this->filter, $filter_fields, $this->order, $this->group, $this->params, $this->limit, $pos, $this->distinct);
	}


	public function getValuesArray($pos = 0) {
		$filter_fields = $this->filter_fields === NULL ? $this->fields : $this->filter_fields;
		return $this->bdd->getQueryValuesArray($this->fields, $this->tables, $this->joint, $this->where, $this->filter, $filter_fields, $this->order, $this->group, $this->params, $this->limit, $pos, $this->distinct);
	}


	public function getCount() {
		$filter_fields = $this->filter_fields === NULL ? $this->fields : $this->filter_fields;
		return $this->bdd->getQueryCount($this->tables, $this->joint, $this->where, $this->filter, $filter_fields, $this->group, $this->params, $this->distinct);
	}


	public function modelData($pos = 0) {
		$md = $this->model->modelData();
		return new $md($this->model, $this->getValues($pos));
	}

}
