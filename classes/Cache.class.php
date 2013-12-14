<?php
configure("CACHE_DIR", DATA_DIR . DIRECTORY_SEPARATOR . "cache");
configure("WWW_CACHE_DIR", WWW_DIR . DIRECTORY_SEPARATOR . "cache");
configure("WWW_CACHE_PATH", WWW_PATH . DIRECTORY_SEPARATOR . "cache");


class Cache {
	private $filename;
	private $filename_cache;


	public function __construct($filename, $filename_cache) {
		$this->filename = $filename;
		$this->filename_cache = $filename_cache;
		ensure_dir(dirname($this->filename_cache));
		/*
		if (! is_writable($this->filename_cache))
			send_error(500, NULL, $this->filename_cache." is not writable");
		*/
	}


	public static function MakeCacheName($filename, $path, $len) {
		if (($dot = strrpos($filename, ".")) !== false)
		  $ext = substr($filename, $dot);
		else
			$ext = "";

		$sha = sha1($filename);
		if ($len > 0) {
			$s = substr($sha, 0, 3);
			return $path . "/" . $s . "/" . $sha . $ext;
		}
		return $path . "/" . $sha . $ext;
	}


	public static function Create($filename, $path, $len = 3) {
		return new self($filename, self::MakeCacheName($filename, $path, $len));
	}


	public static function Priv($filename) {
		return self::Create($filename, CACHE_DIR, 0);
	}


	public static function Pub($filename) {
		return self::Create($filename, WWW_CACHE_DIR, 0);
	}


	public function getContents() {
		return file_get_contents($this->filename_cache);
	}


	public function setContents($value) {
		return file_put_contents($this->filename_cache, $value);
	}


	public function delete() {
		if (file_exists($this->filename_cache)) {
			unlink($this->filename_cache);
		}
	}


	public function getFilename() {
		return $this->filename_cache;
	}


	public function getFilepath() {
		if (substr($this->filename_cache, 0, strlen(WWW_CACHE_DIR)) == WWW_CACHE_DIR)
			return WWW_CACHE_PATH . substr($this->filename_cache, strlen(WWW_CACHE_DIR));
		
		return False;
	}


	public function check() {
		return (!is_file($this->filename_cache) || filemtime($this->filename) > filemtime($this->filename));
	}

}
