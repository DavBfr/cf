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

class Cache {
	private $filename;
	private $filename_cache;


	public function __construct($filename, $filename_cache) {
		$this->filename = $filename;
		$this->filename_cache = $filename_cache;
	}


	public static function MakeCacheName($filename, $path, $len, $ext = NULL) {
		if ($ext === NULL) {
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


	public static function Create($filename, $path, $len = 3, $ext = NULL) {
		return new self($filename, self::MakeCacheName($filename, $path, $len, $ext));
	}


	public static function Priv($filename, $ext = NULL) {
		return self::Create($filename, CACHE_DIR, 0, $ext);
	}


	public static function Pub($filename, $ext = NULL) {
		return self::Create($filename, WWW_CACHE_DIR, 0, $ext);
	}


	public function isWritable() {
		return true;
	}


	public function getContents() {
		return file_get_contents($this->filename_cache);
	}


	public function setContents($value) {
		System::ensureDir(dirname($this->filename_cache));
		//if (! is_writable($this->filename_cache))
		//	ErrorHandler::error(500, NULL, $this->filename_cache." is not writable");
			
		return file_put_contents($this->filename_cache, $value);
	}


	public function openWrite() {
		System::ensureDir(dirname($this->filename_cache));
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


	/**
	* Return true if the cache file is to be (re)created
	**/
	public function check() {
		if (!is_file($this->filename) && is_file($this->filename_cache))
			return false;

		return (!is_file($this->filename_cache) || filemtime($this->filename) > filemtime($this->filename_cache));
	}


	public function symlink() {
		if (!file_exists($this->filename_cache)) {
			symlink($this->filename, $this->filename_cache);
		}
	}

}
