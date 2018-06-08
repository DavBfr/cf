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


	/**
	 * @param int $code
	 */
	public static function finish($code = 0) {
		if ($code == 0) {
			Logger::debug("Response time: " . (microtime(true) - START_TIME));
		}

		if (function_exists('error_clear_last')) {
			error_clear_last();
		}

		exit($code);
	}


	/**
	 * @param array $object
	 */
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


	/**
	 * @param array $object
	 */
	public static function success($object = array()) {
		self::json($object);
	}


	/**
	 * @param string $message
	 * @param int $code
	 * @throws \Exception
	 */
	public static function error($message, $code = 400) {
		ErrorHandler::error($code, $message, json_encode(array("error" => $message)), 3, true);
	}


	/**
	 * @param string $url
	 */
	public static function redirect($url) {
		$protocol = (isset($_SERVER['SERVER_PROTOCOL']) ? $_SERVER['SERVER_PROTOCOL'] : 'HTTP/1.0');
		header("$protocol 302 Redirect");
		header("Location: $url");
		self::finish();
	}


	/**
	 * @param string $filename
	 * @param string|null $data
	 * @param string $type
	 * @param bool $inline
	 * @param bool $cache
	 */
	public static function file($filename, $data = null, $type = "application/binary", $inline = false, $cache = false) {
		header('Content-Description: File Transfer');
		header('Content-Type: ' . $type);
		header('Content-Disposition: ' . ($inline ? 'inline' : 'attachment') . '; filename="' . basename($filename) . '"');
		header('Content-Transfer-Encoding: binary');

		self::fileCache($filename, $data, $cache);
	}


	/**
	 * @param string $filename
	 * @param string|null $data
	 * @param bool $cache
	 */
	public static function fileCache($filename, $data = null, $cache = false) {
		if (DEBUG) $cache = false;

		while (ob_get_length())
			ob_end_clean();

		if ($cache) {
			if ($data === null) {
				$filetime = filemtime($filename);
				$if_modified_since = isset($_SERVER['HTTP_IF_MODIFIED_SINCE']) ? $_SERVER['HTTP_IF_MODIFIED_SINCE'] : false;
				$etag_header = false;
				$etag = '';
			} else {
				$if_modified_since = false;
				$filetime = 0;
				$etag = sha1($data);
				$etag_header = isset($_SERVER['HTTP_IF_NONE_MMATCH']) ? $_SERVER['HTTP_IF_NONE_MMATCH'] : false;
			}

			header_remove("Pragma");
			header("Expires: " . gmdate("D, d M Y H:i:s", time() + CACHE_TIME) . " GMT");

			if (($if_modified_since && strtotime($if_modified_since) == $filetime || ($etag_header && $etag_header == $etag))) {
				header_remove("Cache-Control");
				header('HTTP/1.1 304 Not Modified');
			} else {
				header("Cache-Control: immutable, only-if-cached, public");

				if ($data === null) {
					header("Content-Length: " . filesize($filename));
					header("Last-Modified: " . gmdate("D, d M Y H:i:s", $filetime) . " GMT");

					readfile($filename);
				} else {
					header("Etag: $etag");

					echo $data;
				}
			}
		} else {
			header('Expires: 0');
			header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
			header('Pragma: no-cache');

			if ($data === null) {
				readfile($filename);
			} else {
				echo $data;
			}
		}

		self::finish();
	}


	/**
	 * @param string $message
	 * @throws \Exception
	 */
	public static function debug($message = "") {
		if (!DEBUG)
			return;

		if ($message != "")
			$message = "<pre>" . $message . "</pre>";

		ErrorHandler::error(500, "Debug", $message, 3);
	}

}
