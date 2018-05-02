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

use lessc;
use lessc_formatter_compressed;

class Less extends lessc {
	private $origdir = null;


	public function __construct($fname = null) {
		parent::__construct($fname);
		$this->registerFunction("media", function ($arg) {
			$file = Resources::find($arg[2][0]);
			if ($file !== null) {
				return Resources::web($file);
			}

			return '';
		});
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
			if ($this->fileExists($file = $full . '.less') || $this->fileExists($file = $full)) {
				return $file;
			}
		}

		return null;
	}


	public function enableMinify() {
		$this->setFormatter(new lessc_formatter_compressed());
	}

}
