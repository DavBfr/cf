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

class Output {

	public static function json($object) {
		if (JSON_HEADER)
			header("Content-Type: text/json");
		else
			header("Content-Type: text/plain");
		
		$content = ob_get_contents();
		ob_end_clean();
		if (DEBUG && is_array($object) && strlen($content) > 0) {
			$object["__debug__"] = $content;
		}
		die(json_encode($object));
	}


	public static function success($object = array()) {
		self::json(array_merge($object, array("success"=>true)));
	}


	public static function error($message) {
		self::json(array("error"=>$message, "success"=>false));
	}


	public static function redirect($url) {
		$protocol = (isset($_SERVER['SERVER_PROTOCOL']) ? $_SERVER['SERVER_PROTOCOL'] : 'HTTP/1.0');
		header("$protocol 302 Redirect");
		header("Location: $url");
		die();
	}


	public static function debug($message) {
		if (!DEBUG)
			return;

		ErrorHandler::error(500, "Debug", "<pre>" . $message . "</pre>", 3);
	}
	
}
