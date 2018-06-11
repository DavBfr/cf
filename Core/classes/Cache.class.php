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


	/**
	 * Cache constructor.
	 * @param string $filename
	 * @param string $filename_cache
	 */
	public function __construct($filename, $filename_cache) {
		$this->filename = $filename;
		$this->filename_cache = $filename_cache;
	}


	/**
	 * @param string $filename
	 * @param string $path
	 * @param int $len
	 * @param string|null $ext
	 * @return string
	 */
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


	/**
	 * @param string $filename
	 * @param string $path
	 * @param int $len
	 * @param string|null $ext
	 * @return Cache
	 */
	public static function Create($filename, $path, $len = 3, $ext = null) {
		return new self($filename, self::MakeCacheName($filename, $path, $len, $ext));
	}


	/**
	 * @param string $filename
	 * @param string|null $ext
	 * @return Cache
	 */
	public static function Priv($filename, $ext = null) {
		return self::Create($filename, CACHE_DIR, 0, $ext);
	}


	/**
	 * @param string $filename
	 * @param string|null $ext
	 * @return Cache
	 */
	public static function Pub($filename, $ext = null) {
		return self::Create($filename, WWW_CACHE_DIR, 0, $ext);
	}


	/**
	 * @return bool
	 */
	public function isWritable() {
		if (is_file($this->filename_cache) && is_writable($this->filename_cache))
			return true;

		return is_writable(dirname($this->filename_cache));
	}


	/**
	 * @return bool|string
	 */
	public function getContents() {
		return file_get_contents($this->filename_cache);
	}


	/**
	 * @param string $value
	 * @throws \Exception
	 */
	public function setContents($value) {
		System::ensureDir(dirname($this->filename_cache));
		if (!$this->isWritable()) {
			Logger::error($this->filename_cache . " is not writable");
			return;
		}

		Logger::debug("Write cache $this->filename_cache");
		file_put_contents($this->filename_cache, $value);
	}


	/**
	 * @return array
	 * @throws \Exception
	 */
	public function getArray() {
		try {
			return Input::jsonDecode(file_get_contents($this->filename_cache));
		} catch (\Exception $e) {
			ErrorHandler::error(500, null, "Error in " . $this->filename_cache . ": " . $e);
			die();
		}
	}


	/**
	 * @param array $value
	 * @throws \Exception
	 */
	public function setArray($value) {
		System::ensureDir(dirname($this->filename_cache));

		file_put_contents($this->filename_cache, json_encode($value));
	}


	/**
	 * @return bool|resource
	 * @throws \Exception
	 */
	public function openWrite() {
		System::ensureDir(dirname($this->filename_cache));
		Logger::debug("Write cache $this->filename_cache");
		return fopen($this->filename_cache, "w");
	}


	/**
	 *
	 */
	public function delete() {
		if (file_exists($this->filename_cache)) {
			unlink($this->filename_cache);
		}
	}


	/**
	 * @return string
	 * @throws \Exception
	 */
	public function getFilename() {
		System::ensureDir(dirname($this->filename_cache));
		return $this->filename_cache;
	}


	/**
	 *
	 */
	public function outputIfCached() {
		if ($this->exists()) {
			Output::fileCache($this->filename_cache, null, true);
		}
	}


	/**
	 * Return true if the cache file is to be (re)created
	 * @return bool
	 */
	public function check() {
		$exists = $this->exists();
		if (!is_file($this->filename) && $exists)
			return false;

		return !$exists || filemtime($this->filename) > filemtime($this->filename_cache);
	}


	/**
	 * @return bool
	 */
	public function exists() {
		return CACHE_ENABLED && is_file($this->filename_cache);
	}


	/**
	 *
	 */
	public function symlink() {
		if (!file_exists($this->filename_cache)) {
			System::symlink($this->filename, $this->filename_cache);
		}
	}

}
