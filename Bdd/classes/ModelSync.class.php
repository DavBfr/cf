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

abstract class ModelSync extends RestApi {
	/** @var Model $model */
	protected $model;
	protected $options;


	/**
	 * @param array $r
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
	 */
	private static function defaultOptions() {
		return array(
			"update_field" => "sync", // timestamp of last update
			"transaction_table" => null, // a table with [key, foreign_id, action]
			"can_update" => true,
			"can_delete" => true,
			"can_insert" => true,
			"filter_updated" => false,
			"limit" => CRUD_LIMIT,
		);
	}


	/**
	 * @return array
	 */
	protected function getOptions() {
		return array();
	}


	public function getRoutes() {
		$this->addRoute("/sync/:lastsync", "POST", "doSync");
	}


	/**
	 * @param int $lastsync
	 * @return Collection
	 * @throws \Exception
	 */
	protected function syncCollection($lastsync) {
		$col = Collection::Model($this->model);
		if ($this->options['update_field'] != null) {
			$col->where($this->options['update_field'] . " > :lastsync")->withValue("lastsync", $lastsync);
		}
		return $col;
	}


	/**
	 * @param array $item
	 * @return mixed
	 * @throws \Exception
	 */
	function searchDuplicate(array $item) {
		$id = $this->model->getPrimaryField();
		return $this->model->getBy($id, $item[$id]);
	}


	/**
	 * @param array $item
	 */
	protected function filterValues(array & $item) {
	}


	/**
	 * @param array $r
	 * @throws \Exception
	 */
	protected function doSync($r) {
		Input::ensureRequest($r, array("lastsync"));
		try {
			$json = Input::decodeJsonPost();
		} catch (\Exception $e) {
			Logger::error($e);
			$json = null;
		}

		$id = $this->model->getPrimaryField();
		$updated = array();
		$deleted = array();

		if ($json !== null) {
			if ($this->options['can_delete'] && array_key_exists('delete', $json)) {
				foreach ($json['delete'] as $item) {
					$this->model->deleteById($item);
				}
			}
			if ($this->options['can_update'] && array_key_exists('updated', $json)) {
				// Must update master values from remote
				foreach ($json['updated'] as $item) {
					$this->filterValues($item);
					if (array_key_exists($id, $item) && $item[$id] !== null) {
						$entry = $this->searchDuplicate($item);
						if ($entry->isEmpty())
							$entry = $this->model->newRow();
					} else {
						$entry = $this->model->newRow();
					}

					$oldId = $item[$id];
					unset($item[$id]);
					$entry->setValues($item);
					if ($this->options['update_field'] != null) {
						$entry->set($this->options['update_field'], time());
					}
					if ($entry->isNew()) {
						if (!$this->options['can_insert'])
							continue;
						$deleted[] = $oldId;
					} else {
						$updated[] = intval($entry->get($id));
					}
					$entry->save();
				}
			}
			if ($this->options['can_insert'] && array_key_exists('new', $json)) {
				// Must insert new items to master
				foreach ($json['new'] as $item) {
					$this->filterValues($item);
					$entry = $this->model->newRow();
					$deleted[] = $item[$id];
					unset($item[$id]);
					$entry->setValues($item);
					if ($this->options['update_field'] != null) {
						$entry->set($this->options['update_field'], time());
					}
					$entry->save();
				}
			}
		}

		$col = $this->syncCollection(intval($r["lastsync"]));

		if ($this->options["filter_updated"] && count($updated) > 0) {
			$col->where("$id NOT IN (" . implode(", ", $updated) . ")");
		}

		$items = $col->getValuesArray();
		foreach ($items as $item) {
			unset($deleted[$item[$id]]);
		}

		Output::success(array("items" => $items, "deleted" => $deleted));
	}

}
