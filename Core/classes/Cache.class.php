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

class Cache {
	private $filename; // Original file name
	private $filename_cache; // Cached file name created from $filename


	public function __construct($filename, $filename_cache) {
		$this->filename = $filename;
		$this->filename_cache = $filename_cache;
	}


	public static function MakeCacheName($filename, $path, $len, $ext = null) {
		if ($ext === null) {
			if (($dot = strrpos($filename, ".")) !== false)
				$ext = substr($filename, $dot);
			else
				$ext = "";
		}

		$sha = sha1($filename);
		if ($len > 0) {
			$s = substr($sha, 0, $len);
			return $path . "/" . $s . "/" . $sha . $ext;
		}
		return $path . "/" . $sha . $ext;
	}


	public static function Create($filename, $path, $len = 3, $ext = null) {
		return new self($filename, self::MakeCacheName($filename, $path, $len, $ext));
	}


	public static function Priv($filename, $ext = null) {
		return self::Create($filename, CACHE_DIR, 0, $ext);
	}


	public static function Pub($filename, $ext = null) {
		return self::Create($filename, WWW_CACHE_DIR, 0, $ext);
	}


	public function isWritable() {
		if (is_file($this->filename_cache) && is_writable($this->filename_cache))
			return true;

		return is_writable(dirname($this->filename_cache));
	}


	public function getContents() {
		return file_get_contents($this->filename_cache);
	}


	public function setContents($value) {
		System::ensureDir(dirname($this->filename_cache));
		if (! $this->isWritable()) {
			Logger::Error($this->filename_cache . " is not writable");
			return;
		}

		Logger::Debug("Write cache $this->filename_cache");
		file_put_contents($this->filename_cache, $value);
	}


	public function getArray() {
		try {
			return Input::jsonDecode(file_get_contents($this->filename_cache));
		} catch (\Exception $e) {
			ErrorHandler::error(500, null, "Error in " . $this->filename_cache . ": " . $e);
		}
	}


	public function setArray($value) {
		System::ensureDir(dirname($this->filename_cache));

		file_put_contents($this->filename_cache, json_encode($value));
	}


	public function openWrite() {
		System::ensureDir(dirname($this->filename_cache));
		Logger::Debug("Write cache $this->filename_cache");
		return fopen($this->filename_cache, "w");
	}


	public function delete() {
		if (file_exists($this->filename_cache)) {
			unlink($this->filename_cache);
		}
	}


	public function getFilename() {
		System::ensureDir(dirname($this->filename_cache));
		return $this->filename_cache;
	}


	public function outputIfCached() {
		if ($this->exists()) {
			$filetime = filemtime($this->filename_cache);
			header("Date: " . gmdate("D, d M Y H:i:s", time())." GMT");
			header("Last-Modified: ".gmdate("D, d M Y H:i:s", $filetime)." GMT");
			header("Expires: " . gmdate("D, d M Y H:i:s", time() + CACHE_TIME)." GMT");
			header("Cache-Control: private, max-age=" . CACHE_TIME);
			$if_modified_since = isset($_SERVER['HTTP_IF_MODIFIED_SINCE']) ? $_SERVER['HTTP_IF_MODIFIED_SINCE'] : false;

			if ($if_modified_since && strtotime($if_modified_since) == $filetime) {
				header('HTTP/1.1 304 Not Modified');
			} else {
				echo $this->getContents();
			}

			Output::finish();
		}
	}


	/**
	* Return true if the cache file is to be (re)created
	**/
	public function check() {
		$exists = $this->exists();
		if (!is_file($this->filename) && $exists)
			return false;

		return !$exists || filemtime($this->filename) > filemtime($this->filename_cache);
	}


	public function exists() {
		return CACHE_ENABLED && is_file($this->filename_cache);
	}


	public function symlink() {
		if (!file_exists($this->filename_cache)) {
			System::symlink($this->filename, $this->filename_cache);
		}
	}

}
