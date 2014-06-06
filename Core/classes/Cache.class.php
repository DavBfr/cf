<?php

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
			$s = substr($sha, 0, 3);
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
		return True;
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


	public function check() {
		return (!is_file($this->filename_cache) || filemtime($this->filename) > filemtime($this->filename));
	}

}
