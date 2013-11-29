<?php

class Minifier {
	private $scripts = array();
	private $stylesheets = array();
	private $search = array();

	public function __construct() {
		$this->set_search_path();
	}
	
	protected function set_search_path() {
		$this->search[] = array(MEDIA_DIR, MEDIA_PATH);
		$this->search[] = array(VENDOR_DIR, VENDOR_PATH);
		$this->search[] = array(CF_VENDOR_DIR, CF_VENDOR_PATH);
	}

	private function is_min($filename) {
		if (($dot = strrpos($filename, ".")) !== false)
		  $type = substr($filename, $dot+1);
		else
			return $filename;

		if (substr($filename, -strlen($type) - 4) == "min." . $type) {
			return True;
		}

		return False;
	}

	private function min($filename) {
		if (($dot = strrpos($filename, ".")) !== false)
		  $type = substr($filename, $dot+1);
		else
			return $filename;

		if (substr($filename, -strlen($type) - 4) == "min." . $type) {
			return $filename;
		}

		return substr($filename, 0, -strlen($type)) . "min." . $type;
	}
	
	private function has_min($filename) {
		$filename_min = $this->min($filename);
		if (file_exists($filename_min)) {
			return $filename_min;
		}

		return false;
	}

	public function add_dir($dir, $path) {
		$dirs = array();
		if (is_dir($dir)) {
			if ($dh = opendir($dir)) {
				while (($file = readdir($dh)) !== false) {
					if ($file[0] != ".") {
						$file = $dir . DIRECTORY_SEPARATOR . $file;
						if (filetype($file) == "dir") {
							$dirs[] = $file;
						} elseif (substr($file, -3) == ".js") {
							$this->scripts[] = array($file, $path . URL_SEPARATOR . basename($file));
						} elseif (substr($file, -4) == ".css") {
							$this->stylesheets[] = array($file, $path);
						}
					}
				}
				closedir($dh);
				foreach ($dirs as $dir) {
					$this->add_dir($dir, $path . URL_SEPARATOR . basename($dir));
				}
			}
		}
	}

	private function generate($items, $output, $type) {
		$outputwww = MEDIA_PATH . URL_SEPARATOR . $output;
		$output = MEDIA_DIR . DIRECTORY_SEPARATOR . $output;
		if (DEBUG) {
			if (file_exists($output)) {
				unlink($output);
			}
			return $items;
		}

		$time = @filemtime($output);
		$generate = False;
		foreach($items as $item) {
			$t = @filemtime($item[0]);
			if ($t > $time)
				$generate = True;
		}

		if ($generate) {
			if (!is_writable(dirname($output))) {
				return $items;
			}

			$data = Array();
			foreach($items as $item) {
				if ($this->is_min($item[0])) {
					$data[] = file_get_contents($item[0]);
				} elseif (($item_min = $this->has_min($item[0])) !== false) {
					$data[] = file_get_contents($item_min);
				} else {
					exec("yui-compressor --nomunge --type $type '${item[0]}'", $data, $ret);
					if ($ret !== 0) {
						$data[] = file_get_contents($item[0]);
					}
				}
			}
			if ($f = fopen($output, "w")) {
				fwrite($f, implode("\n", $data));
				fclose($f);
			}
		}

		return Array(Array($output, $outputwww));
	}
	
	public function add($filename) {
		foreach($this->search as $dirpath) {
			list($dir, $path) = $dirpath;
			$file = $dir . DIRECTORY_SEPARATOR . $filename;
			$filepath = $path . URL_SEPARATOR . $filename;
			if (! file_exists($file)) {
				if (($file = $this->has_min($file)) === false) {
					continue;
				}
				$filepath = $path . URL_SEPARATOR . $this->min($filename);
			}
			if (substr($file, -3) == ".js") {
				$this->scripts[] = array($file, $filepath);
				return;
			} elseif (substr($file, -4) == ".css") {
				$this->stylesheets[] = array($file, $filepath);
				return;
			}
		}
		send_error(500, "Internal server error", "file ${filename} not found");
	}

	public function get_scripts() {
		$wwwfiles = array();
		foreach ($this->generate($this->scripts, "app.min.js", "js") as $filepath) {
			$wwwfiles[] = $filepath[1];
		}
		return $wwwfiles;
	}

	public function get_stylesheets() {
		$wwwfiles = array();
		foreach ($this->generate($this->stylesheets, "app.min.css", "css") as $filepath) {
			$wwwfiles[] = $filepath[1];
		}
		return $wwwfiles;
	}

}
