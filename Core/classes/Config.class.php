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

class Config implements arrayaccess {
	private static $instance = NULL;
	private $data;


	public function __construct() {
		$this->data = array();
	}


	public static function getInstance() {
		if (is_null(self::$instance)) {
			self::$instance = new self();
			$memcache = new MemCache();
			if (array_key_exists("JCONFIG_FILE", $memcache)) {
				self::$instance->data = $memcache["JCONFIG_FILE"];
				Logger::debug("Config loaded from cache");
			}
			elseif (file_exists(JCONFIG_FILE)) {
				self::$instance->load(JCONFIG_FILE);
				$memcache["JCONFIG_FILE"] = self::$instance->data;
			}
		}

		return self::$instance;
	}


	public static function jsonLastErrorMsg() {
		if (!function_exists('json_last_error_msg')) {
			static $errors = array(
					JSON_ERROR_NONE             => null,
					JSON_ERROR_DEPTH            => 'Maximum stack depth exceeded',
					JSON_ERROR_STATE_MISMATCH   => 'Underflow or the modes mismatch',
					JSON_ERROR_CTRL_CHAR        => 'Unexpected control character found',
					JSON_ERROR_SYNTAX           => 'Syntax error, malformed JSON',
					JSON_ERROR_UTF8             => 'Malformed UTF-8 characters, possibly incorrectly encoded'
			);
			$error = json_last_error();
			return array_key_exists($error, $errors) ? $errors[$error] : "Unknown error ({$error})";
		}

		return json_last_error_msg();
	}


	public function load($filename) {
		$this->data = json_decode(file_get_contents($filename), true);
		if (json_last_error() !== JSON_ERROR_NONE) {
			ErrorHandler::error(500, NULL, "Error in ${filename} : " . self::jsonLastErrorMsg()); break;
		}
	}


	public function save($filename) {
		file_put_contents($filename, json_encode($this->data));
	}


	public function get($key, $default=NULL) {
		$value = $this->data;
		foreach(explode(".", $key) as $item) {
			if (is_array($value) && array_key_exists($item, $value)) {
				$value = $value[$item];
			} else {
				return $default;
			}
		}

		return $value;
	}


	public function offsetSet($offset, $value) {
		$this->data[$offset] = $value;
	}


	public function offsetExists($offset) {
		return $this->get($key) !== NULL;
	}


	public function offsetUnset($offset) {
	}


	public function offsetGet($offset) {
		return $this->get($key);
	}

}
