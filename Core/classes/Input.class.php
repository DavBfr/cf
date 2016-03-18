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

class Input {

	public static function decodeJsonPost() {
		return json_decode(file_get_contents("php://input"), true);
	}


	public static function ensureRequest($array, $mandatory, $optional = array(), $strict = false) {
		foreach($mandatory as $param) {
			if (!isset($array[$param])) {
				ErrorHandler::error(417, null, "Missing parameter ${param}");
			}
			if ($array[$param] == "") {
				ErrorHandler::error(417, null, "Empty parameter ${param}");
			}
		}

		if ($strict) {
			foreach($array as $param => $val) {
				if (!(in_array($param, $mandatory) || in_array($param, $optional))) {
					ErrorHandler::error(417, null, "Parameter overly ${param}");
				}
			}
		}
	}


	public static function has($name) {
		return isset($_GET[$name]);
	}


	public static function get($name, $default = null) {
		return self::protect($_GET[$name]);
	}


	public static function protect($data) {
		if (is_array($data)) {
			return array_map(self::protect, $data);
		}

		$data = trim($data);
		$data = htmlentities($data, ENT_QUOTES, "UTF-8");
		$data = preg_replace('/\\x00/', '', preg_replace('/\\\0/', '', $data));
		return $data;
	}

}
