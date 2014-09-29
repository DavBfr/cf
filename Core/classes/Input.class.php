<?php
/**
 * Copyright (C) 2013 David PHAM-VAN
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

class Input {

	public static function decodeJsonPost() {
		return json_decode(file_get_contents("php://input"), true);
	}


	public static function ensureRequest($array, $mandatory, $optional = array(), $strict = false) {
		foreach($mandatory as $param) {
			if (!isset($array[$param])) {
				ErrorHandler::error(417, NULL, "Missing parameter ${param}");
			}
			if ($array[$param] == "") {
				ErrorHandler::error(417, NULL, "Empty parameter ${param}");
			}
		}

		if ($strict) {
			foreach($array as $param => $val) {
				if (!(in_array($param, $mandatory) || in_array($param, $optional))) {
					ErrorHandler::error(417, NULL, "Parameter overly ${param}");
				}
			}
		}
	}

}
