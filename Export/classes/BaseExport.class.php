<?php namespace DavBfr\CF;
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

abstract class BaseExport {
	protected $stream;


	function __construct($stream = 'php://temp') {
		$this->stream = fopen($stream, 'r+');
		$this->start();
	}


	/**
	 * @return BaseExport
	 */
	public static function newDefault() {
		if (Options::get('DEBUG'))
			return new HtmlExport();

		if (class_exists('\OneSheet\Sheet'))
			return new ExcelExport();

		return new CsvExport();
	}


	abstract protected function start();


	/**
	 * @param array $data
	 */
	public function header(array $data) {
		$this->add($data);
	}


	/**
	 * @param array $data
	 * @return void
	 */
	abstract public function add(array $data);


	/**
	 * @return string
	 */
	abstract public function mimeType();


	/**
	 * @return string
	 */
	abstract public function fileExtension();


	/**
	 * @return string Data
	 */
	public function end() {
		rewind($this->stream);
		$data = stream_get_contents($this->stream);
		fclose($this->stream);
		return $data;
	}


	/**
	 * Output as file
	 * @param string $filename
	 * @param bool $inline
	 */
	public function output($filename, $inline = false) {
		Output::file($filename, $this->end(), $this->mimeType(), $inline);
	}


	public function outputDefault($prefix) {
		if (Options::get('DEBUG') && is_a($this, 'HtmlExport'))
			die($this->end());
		else
			$this->output($prefix . "-" . date("Y-m-d-His") . $this->fileExtension(), true);
	}


	/**
	 * @param ModelField $field
	 * @param $value
	 * @throws \Exception
	 */
	public function dataFilter(ModelField $field, &$value) {
		switch($field->getType()) {
			case 'ts':
				$value = date(Lang::get('core.datetime'), $value);
				break;
			case 'bool':
				$value = $value ? Lang::get('core.yes') : Lang::get('core.no');
				break;
		}
	}

	/**
	 * @param Model $model
	 * @throws \Exception
	 */
	public function exportModel(Model $model) {
		$config = Config::getInstance();
		$col = Collection::Query($model->getTableName());
		$hd = array();
		foreach ($model->getFields() as $field) {
			if ($config->get("model." . $field->getTableName() . "." . $field->getName() . ".export", true)) {
				$hd[] = $field->getCaption();
				$col->select($field->getName());
			}
		}
		$this->header($hd);
		foreach ($col->getValues() as $item) {
			foreach ($item as $name => &$value) {
				$this->dataFilter($model->getField($name), $value);
			}
			$this->add($item);
		}
	}
}
