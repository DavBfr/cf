<?php namespace DavBfr\CF;

/**
 * Copyright (C) 2013-2016 David PHAM-VAN
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

class Less {
	private $origdir = '';
	private $options = [];
	private $functions = [];


	public function __construct($fname = null) {
		$this->options['import_callback'] = [$this, 'findImport'];

		$this->registerFunction("media", function ($arg) {
			$file = Resources::find($arg[2][0]);
			if ($file !== null) {
				return Resources::web($file);
			}

			return '';
		});
	}

	public function compileFile($fname, $outFname = null) {
		$parser = new \Less_Parser($this->options);
		foreach ($this->functions as $name => $func) {
			$parser->registerFunction($name, $func);
		}
		$parser->parseFile($fname, $this->origdir);
		$css = $parser->getCss();

		if ($outFname !== null) {
			return file_put_contents($outFname, $css);
		}

		return $css;
	}

	public function registerFunction($name, $func) {
		$this->functions[$name] = $func;
	}

	public function setOriginalDir($dir) {
		$this->origdir = $dir;
	}


	protected function findImport($url) {
		if ($this->origdir) {
			$partial = $this->origdir . '/' . $url;
			if ($res = AbstractResources::find($partial)) {
				return $res;
			}
			if ($res = AbstractResources::find($partial . '.less')) {
				return $res;
			}
		}

		foreach ((array)$this->importDir as $dir) {
			$full = $dir . (substr($dir, -1) != '/' ? '/' : '') . $url;
			if (is_file($file = $full . '.less') || is_file($file = $full)) {
				return $file;
			}
		}

		return null;
	}


	public function enableMinify() {
		$this->options['compress'] = true;
	}

}
