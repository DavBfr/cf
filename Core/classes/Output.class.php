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

class Output {


	public static function finish($code = 0) {
		if ($code == 0) {
			Logger::debug("Response time: " . (microtime(true) - START_TIME));
		}

		error_clear_last();
		exit($code);
	}


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
		echo json_encode($object);
		if (DEBUG)
			echo "\n";
		self::finish();
	}


	public static function success($object = array()) {
		self::json($object);
	}


	public static function error($message, $code = 400) {
		ErrorHandler::error($code, $message, json_encode(array("error" => $message)), 3, true);
	}


	public static function redirect($url) {
		$protocol = (isset($_SERVER['SERVER_PROTOCOL']) ? $_SERVER['SERVER_PROTOCOL'] : 'HTTP/1.0');
		header("$protocol 302 Redirect");
		header("Location: $url");
		self::finish();
	}


	public static function file($filename, $data, $type = "application/binary", $inline = false) {
		header('Content-Description: File Transfer');
		header('Content-Type: ' . $type);
		header('Content-Disposition: ' . ($inline ? 'inline' : 'attachment') . '; filename="' . $filename . '"');
		header('Content-Transfer-Encoding: binary');
		header('Expires: 0');
		header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
		header('Pragma: public');

		while (ob_get_length())
			ob_end_clean();

		echo $data;
		self::finish();
	}


	public static function debug($message = "") {
		if (!DEBUG)
			return;

		if ($message != "")
			$message = "<pre>" . $message . "</pre>";

		ErrorHandler::error(500, "Debug", $message, 3);
	}

}
