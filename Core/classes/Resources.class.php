<?php

class Resources {
	const WWW_DIR = "www";
	const VENDOR_DIR = "www/vendor";

	private $resources;


	public function __construct() {
		$this->resources = array();
	}


	public static function find($filename) {
		if (($resource = Plugins::find(self::WWW_DIR . DIRECTORY_SEPARATOR . $filename)) !== NULL)
			return $resource;
		if (($resource = Plugins::find(self::VENDOR_DIR . DIRECTORY_SEPARATOR . $filename)) !== NULL)
			return $resource;
		return NULL;
	}


	public static function web($filename) {
		$localpath = WWW_DIR . DIRECTORY_SEPARATOR;
		if (strpos($filename, $localpath) !== False) {
			$web = WWW_PATH . DIRECTORY_SEPARATOR . str_replace($localpath, '', $filename);
		} else {
			$web = str_replace(DOCUMENT_ROOT, '', $filename);
		}
		return $web;
	}


	public function addDir($dir) {
		$dirs = array();
		if (is_dir($dir)) {
			if ($dh = opendir($dir)) {
				while (($file = readdir($dh)) !== false) {
					if ($file[0] != ".") {
						$file = $dir . DIRECTORY_SEPARATOR . $file;
						if (filetype($file) == "dir") {
							$dirs[] = $file;
						} else {
							$this->append($file);
						}
					}
				}
				closedir($dh);
				foreach ($dirs as $dir) {
					$this->addDir($dir, $path . URL_SEPARATOR . basename($dir));
				}
			}
		}
	}


	public function add($filename) {
		$this->append(self::find($filename));
	}
	
	
	protected function append($filename) {
		$this->resources[] = $filename;
	}


	public function getResources() {
		return array_map(array(self, "web"), $this->resources);
	}
	
	
	public function getResourcesByExt($ext) {
		$l = strlen($ext);
		$res = array();
		foreach ($this->resources as $r) {
			if (substr($r, -$l) == $ext) {
				$res[] = $r;
			}
		}
		return $res;
	}


	public function getScripts() {
		return array_map(array(self, "web"), $this->getResourcesByExt(".js"));
	}


	public function getStylesheets() {
		return array_map(array(self, "web"), $this->getResourcesByExt(".css"));
	}

}
