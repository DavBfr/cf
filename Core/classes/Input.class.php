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

class Input {

	/**
	 * @return array
	 * @throws \Exception
	 */
	public static function decodeJsonPost() {
		return self::jsonDecode(file_get_contents("php://input"));
	}


	/**
	 * @param array $array
	 * @param array $mandatory
	 * @param array $optional
	 * @param bool $strict
	 * @throws \Exception
	 */
	public static function ensureRequest($array, $mandatory, $optional = array(), $strict = false) {
		foreach ($mandatory as $param) {
			if (!isset($array[$param])) {
				ErrorHandler::error(417, null, "Missing parameter ${param}");
			}
			if ($array[$param] == "") {
				ErrorHandler::error(417, null, "Empty parameter ${param}");
			}
		}

		if ($strict) {
			foreach ($array as $param => $val) {
				if (!(in_array($param, $mandatory) || in_array($param, $optional))) {
					ErrorHandler::error(417, null, "Parameter overly ${param}");
				}
			}
		}
	}


	/**
	 * @param string $name
	 * @return bool
	 */
	public static function has($name) {
		return isset($_GET[$name]);
	}


	/**
	 * @param string $name
	 * @param string $default
	 * @return string
	 */
	public static function get($name, $default = null) {
		return self::protect($_GET[$name]);
	}


	/**
	 * @param string $data
	 * @return string
	 */
	public static function protect($data) {
		if (is_array($data)) {
			return array_map('self::protect', $data);
		}

		$data = trim($data);
		$data = preg_replace('/\\x00/', '', preg_replace('/\\\0/', '', $data));
		return $data;
	}


	/**
	 * @return string
	 */
	private static function jsonLastErrorMsg() {
		if (!function_exists('json_last_error_msg')) {
			static $errors = array(
				JSON_ERROR_NONE => null,
				JSON_ERROR_DEPTH => 'Maximum stack depth exceeded',
				JSON_ERROR_STATE_MISMATCH => 'Underflow or the modes mismatch',
				JSON_ERROR_CTRL_CHAR => 'Unexpected control character found',
				JSON_ERROR_SYNTAX => 'Syntax error, malformed JSON',
				JSON_ERROR_UTF8 => 'Malformed UTF-8 characters, possibly incorrectly encoded'
			);
			$error = json_last_error();
			return array_key_exists($error, $errors) ? $errors[$error] : "Unknown error ({$error})";
		}

		return json_last_error_msg();
	}


	/**
	 * @param string $json
	 * @return array
	 * @throws \Exception
	 */
	public static function jsonDecode($json) {
		$data = json_decode($json, true);

		if (json_last_error() !== JSON_ERROR_NONE) {
			throw new \Exception("Error decoding json data: " . self::jsonLastErrorMsg());
		}

		return $data;
	}

}
