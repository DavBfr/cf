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

abstract class AbstractResources {
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
		if (strpos($filename, $localpath) !== false) {
			return WWW_PATH . DIRECTORY_SEPARATOR . str_replace($localpath, '', $filename);
		} else {
			if (strpos($filename, DOCUMENT_ROOT) === 0) {
				return str_replace(DOCUMENT_ROOT, '', $filename);
			} else {
				$cache = Cache::Pub($filename);
				$cache->symlink();
				return self::web($cache->getFilename());
			}
		}
	}


	public function addDir($dir) {
		$dirs = array();
		if (is_dir($dir)) {
			if ($dh = opendir($dir)) {
				while (($file = readdir($dh)) !== false) {
					if ($file[0] != ".") {
						$file = $dir . DIRECTORY_SEPARATOR . $file;
						if (is_dir($file)) {
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
