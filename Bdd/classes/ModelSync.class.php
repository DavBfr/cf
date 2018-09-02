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


/**
 * Class ModelSync
 * @package DavBfr\CF
 *
 * Receives a list of update from the client as a json data:
 * {
 *   delete: [id, id, id, ...],
 *   updated: [{field_set}, {field_set}, ...],
 *   new: [{field_set}, {field_set}, ...]
 * }
 *
 * if bidirectional it will send back the locally updated data
 * {
 *   items: [{field_set}, {field_set}, ...],
 *   deleted: [id, id, id, ...],
 * }
 *
 * if the index is new, the item will be added to items and the old will be in deleted
 * so the client must delete first.
 */

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
			"update_field" => null, // timestamp of last update
			"transaction_table" => null, // a table with [key, foreign_id, action]
			"can_update" => true,
			"can_delete" => true,
			"can_insert" => true,
			"bidirectional" => true,
			"filter_updated" => false,
			"keymap" => array(),
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
		$this->addRoute("/sync", "POST", "doSync");
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
	protected function mapKeys(array & $item) {
		foreach ($this->options['keymap'] as $old => $new) {
			if (array_key_exists($old, $item)) {
				$item[$new] = $item[$old];
				unset($item[$old]);
			}
		}
	}


	/**
	 * @param array $item
	 */
	protected function unmapKeys(array & $item) {
		foreach ($this->options['keymap'] as $new => $old) {
			if (array_key_exists($old, $item)) {
				$item[$new] = $item[$old];
				unset($item[$old]);
			}
		}
	}


	/**
	 * @param array $item
	 */
	protected function filterValues(array & $item) {
	}


	/**
	 * @param array $r
	 * @return array
	 */
	protected function syncEnd(array $r) {
		return array();
	}


	/**
	 * @param ModelData $entry
	 * @param array $values
	 * @throws \Exception
	 */
	protected function onInsert($entry, array $values) {
		$entry->setValues($values);
		if ($this->options['update_field'] != null) {
			$entry->set($this->options['update_field'], time());
		}
		$entry->save();
	}


	/**
	 * @param ModelData $entry
	 * @param array $values
	 * @throws \Exception
	 */
	protected function onUpdate($entry, array $values) {
		$entry->setValues($values);
		if ($this->options['update_field'] != null) {
			$entry->set($this->options['update_field'], time());
		}
		$entry->save();
	}


	/**
	 * @param int $id
	 * @throws \Exception
	 */
	protected function onDelete($id) {
		$this->model->deleteById($id);
	}


	/**
	 * @param array $r
	 * @throws \Exception
	 */
	protected function doSync($r) {
		Input::ensureRequest($r, array(), array("lastsync"));

		$sync = time();

		if (!array_key_exists("lastsync", $r))
			$r["lastsync"] = 0;

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
					$this->onDelete($item);
				}
			}
			if ($this->options['can_update'] && array_key_exists('updated', $json)) {
				// Must update master values from remote
				foreach ($json['updated'] as $item) {
					$this->mapKeys($item);
					$this->filterValues($item);
					if (array_key_exists($id, $item) && $item[$id] !== null) {
						$entry = $this->searchDuplicate($item);
						if ($entry === null || $entry->isEmpty())
							$entry = $this->model->newRow();
					} else {
						$entry = $this->model->newRow();
					}

					$oldId = $item[$id];
					unset($item[$id]);
					if ($this->options['update_field'] != null) {
						$item[$this->options['update_field']] = $sync;
					}
					if ($entry->isNew()) {
						if (!$this->options['can_insert'])
							continue;
						$this->onInsert($entry, $item);
						if ($this->options["bidirectional"]) {
							$deleted[] = $oldId;
						}
					} else {
						$this->onUpdate($entry, $item);
						$updated[] = intval($entry->get($id));
					}
				}
			}
			if ($this->options['can_insert'] && array_key_exists('new', $json)) {
				// Must insert new items to master
				foreach ($json['new'] as $item) {
					$this->mapKeys($item);
					$this->filterValues($item);
					$entry = $this->model->newRow();
					if ($this->options["bidirectional"]) {
						$deleted[] = $item[$id];
					}
					unset($item[$id]);
					if ($this->options['update_field'] != null) {
						$item[$this->options['update_field']] = $sync;
					}
					$this->onInsert($entry, $item);
				}
			}
		}

		if ($this->options["bidirectional"]) {
			$col = $this->syncCollection(intval($r["lastsync"]));

			if ($this->options["filter_updated"] && count($updated) > 0) {
				$col->where("$id NOT IN (" . implode(", ", $updated) . ")");
			}

			$items = $col->getValuesArray();
			foreach ($items as &$item) {
				unset($deleted[$item[$id]]);
				$this->unmapKeys($item);
			}
		} else $items = null;

		Output::success(array_merge(array("items" => $items, "deleted" => $deleted, "sync" => $sync), $this->syncEnd($r)));
	}

}
