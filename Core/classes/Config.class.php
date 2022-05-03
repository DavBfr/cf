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

class Config implements \ArrayAccess {
	private static $instance = null;
	private $data;


	/**
	 * Config constructor.
	 */
	public function __construct() {
		$this->data = array();
	}


	/**
	 * @return Config
	 */
	public static function getInstance() {
		if (is_null(self::$instance)) {
			self::$instance = new self();
		}

		return self::$instance;
	}


	/**
	 * @param string $filename
	 * @throws \Exception
	 */
	public function load($filename) {
		$this->data = array();
		$this->append($filename);
	}


	/**
	 * @param array $array
	 */
	public function setData($array) {
		$this->data = $array;
	}


	/**
	 * @return array
	 */
	public function getData() {
		return $this->data;
	}


	/**
	 * @param string $filename
	 * @param bool $reverse
	 * @param string $subkey
	 * @throws \Exception
	 */
	public function append($filename, $reverse = false, $subkey = null) {
		try {
			$data = Input::jsonDecode(file_get_contents($filename));
		} catch (\Exception $e) {
			ErrorHandler::error(500, null, "Error in $filename: $e");
			die();
		}

		if ($subkey !== null) {
			$data = array($subkey => $data);
		}
		$this->merge($data, $reverse);
		Logger::debug("Config $filename loaded");
	}


	/**
	 * @param array $data
	 * @param bool $reverse
	 */
	public function merge($data, $reverse = false) {
		if ($reverse) {
			$this->data = self::arrayMerge($data, $this->data);
		} else {
			$this->data = self::arrayMerge($this->data, $data);
		}
	}


	/**
	 * @param string $key
	 * @param string $filename
	 * @throws \Exception
	 */
	public function loadAsKey($key, $filename) {
		try {
			$data = Input::jsonDecode(file_get_contents($filename));
		} catch (\Exception $e) {
			ErrorHandler::error(500, null, "Error in $filename: $e");
			die();
		}

		$this->data[$key] = $data;
		Logger::debug("Config $filename loaded");
	}


	/**
	 * @param array $array1
	 * @param array $array2
	 * @return array
	 */
	private static function arrayMerge(&$array1, &$array2) {
		$merged = $array1;
		foreach ($array2 as $key => &$value) {
			if (is_array($value) && isset($merged[$key]) && is_array($merged[$key])) {
				$merged[$key] = self::arrayMerge($merged[$key], $value);
			} else {
				$merged[$key] = $value;
			}
		}
		return $merged;
	}


	/**
	 * @param string $filename
	 */
	public function save($filename) {
		file_put_contents($filename, $this->getAsJson());
	}


	/**
	 * @return string
	 */
	public function getAsJson() {
		return json_encode($this->data);
	}


	/**
	 * @param string $key
	 * @param mixed $default
	 * @return mixed
	 */
	public function get($key, $default = null) {
		$value = $this->data;
		foreach (explode(".", $key) as $item) {
			if (is_array($value) && array_key_exists($item, $value)) {
				$value = $value[$item];
			} else {
				return $default;
			}
		}

		return $value;
	}


	/**
	 * @param string $key
	 * @param mixed $val
	 */
	public function set($key, $val) {
		$this->data[$key] = $val;
	}


	/**
	 * @param string $offset
	 * @param mixed $value
	 */
	public function offsetSet(mixed $offset, mixed $value): void {
		$this->set($offset, $value);
	}


	/**
	 * @param string $offset
	 * @return bool
	 */
	public function offsetExists(mixed $offset): bool {
		return $this->get($offset) !== null;
	}


	/**
	 * @param string $offset
	 */
	public function offsetUnset(mixed $offset): void {
		$this->set($offset, null);
	}


	/**
	 * @param string $offset
	 * @return mixed
	 */
	public function offsetGet(mixed $offset): mixed {
		return $this->get($offset);
	}

}
