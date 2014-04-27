<?php

configure("MINIFY_JSCSS", !DEBUG);

class Minifier extends Resources {


	protected function append($filename) {
		if (substr($filename, -5) == ".less") {
			
			$script = 
			Cache::Pub(
			$filename, ".css");
			
			if ($script->check()) {
				$less = new lessc();
				$less->compileFile($filename, $script->getFilename());
			}
			$filename = $script->getFilename();
		}
		parent::append($filename);
	}


	private function isMin($filename) {
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


	private function hasMin($filename) {
		$filename_min = $this->min($filename);
		if (file_exists($filename_min)) {
			return $filename_min;
		}

		return false;
	}


	protected function minifyJavascript($filename) {
		if ($this->isMin($filename)) {
			return file_get_contents($filename);
		} elseif (($item_min = $this->hasMin($filename)) !== false) {
			return file_get_contents($item_min);
		} else {
			$min = Cache::Priv($filename);
			if ($min->check()) {
				exec("yui-compressor --nomunge --type js '${filename}'", $datamin, $ret);
				if ($ret !== 0) {
					$datamin = file_get_contents($filename);
				}
				$min->setContents($datamin);
				return $datamin;
			} else {
				return $min->getContents();
			}
		}
	}
	
	
	protected function minifyStylesheet($filename) {
		if ($this->isMin($filename)) {
			return file_get_contents($filename);
		} elseif (($item_min = $this->hasMin($filename)) !== false) {
			return file_get_contents($item_min);
		} else {
			$min = Cache::Priv($filename);
			if ($min->check()) {
				exec("yui-compressor --nomunge --type css '${filename}'", $datamin, $ret);
				if ($ret !== 0) {
					$datamin = file_get_contents($filename);
				}
				$min->setContents($datamin);
				return $datamin;
			} else {
				return $min->getContents();
			}
		}
	}
	
	
	public function getScripts() {
		$res = $this->getResourcesByExt(".js");
		$output = Cache::Pub("app.min.js");
		if (! MINIFY_JSCSS) {
			$output->delete();
			return array_map(array($this, "web"), $res);
		}
		$out = $output->openWrite();
		foreach($res as $item) {
			fwrite($out, $this->minifyJavascript($item));
		}
		fclose($out);
		return array($this->web($output->getFilename()));
	}
	
	
	public function getStylesheets() {
		$res = $this->getResourcesByExt(".css");
		$output = Cache::Pub("app.min.css");
		if (! MINIFY_JSCSS) {
			$output->delete();
			return array_map(array($this, "web"), $res);
		}
		$out = $output->openWrite();
		foreach($res as $item) {
			fwrite($out, $this->minifyStylesheet($item));
		}
		fclose($out);
		return array($this->web($output->getFilename()));
	}

}