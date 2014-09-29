<?php
/**
 * Copyright (C) 2013 David PHAM-VAN
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

class Minifier extends Resources {

	protected function append($filename) {
		if (substr($filename, -5) == ".less") {
			$script =Cache::Pub($filename, ".css");
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
				if (MINIFY_YUI) {
					exec("yui-compressor --nomunge --type js '${filename}'", $datamin, $ret);
					if ($ret !== 0) {
						$datamin = file_get_contents($filename);
					}
				} else {
					$datamin = JSMin::minify(file_get_contents($filename));
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
				if (MINIFY_YUI) {
					exec("yui-compressor --nomunge --type css '${filename}'", $datamin, $ret);
					if ($ret !== 0) {
						$datamin = file_get_contents($filename);
					}
				} else {
					$less = new lessc();
					$less->setFormatter(new lessc_formatter_compressed());
					$datamin = $less->compileFile($filename);
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
			fwrite($out, $this->minifyJavascript($item)."\n");
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
			fwrite($out, $this->minifyStylesheet($item)."\n");
		}
		fclose($out);
		return array($this->web($output->getFilename()));
	}

}
