<?php namespace DavBfr\CF;
/**
 * Copyright (C) 2013-2015 David PHAM-VAN
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

class System {

	public static function ensureDir($name, $mode=0750) {
		if (! is_dir($name)) {
			if (! mkdir($name, $mode, true)) {
				ErrorHandler::error(500, NULL, "Unable to create directory $name");
			}
		}
	}


	public static function publish($resource, $dest = NULL) {
		if ($dest === NULL)
			$dest = WWW_DIR . "/" . basename($resource);
		else
			$dest = WWW_DIR . "/" . $dest;

		Logger::debug("publish $resource => $dest");

		if (is_link($dest))
			unlink($dest);

		if (! file_exists($dest)) {
			self::symlink($resource, $dest);
		}
	}


	public static function symlink($src, $dst) {
		if (@symlink($src, $dst) === false) {
			if (is_dir($src))
				self::copyTree($src, $dst);
			else
				copy($src, $dst);
		}
	}


	public static function copyTree($src, $dst) {
		$dir = opendir($src);
		@mkdir($dst);
		while(false !== ( $file = readdir($dir)) ) {
			if (( $file != '.' ) && ( $file != '..' )) {
				if ( is_dir($src . DIRECTORY_SEPARATOR . $file) ) {
					self::copyTree($src . DIRECTORY_SEPARATOR . $file, $dst . DIRECTORY_SEPARATOR . $file);
				}
				else {
					copy($src . DIRECTORY_SEPARATOR . $file, $dst . DIRECTORY_SEPARATOR . $file);
					Logger::info("Copy $src/$file to $dst");
				}
			}
		}
		closedir($dir);
	}


	public static function rmtree($path) {
		if (is_dir($path)) {
			foreach (glob("{$path}/*") as $file) {
				if (is_dir($file)) {
					self::rmtree($file);
				} else {
					Logger::info("Delete $file");
					unlink($file);
				}
			}
			rmdir($path);
		}
	}


	public static function sanitize($filename) {
		$filename = preg_replace("([^\w\d\-_.])", '-', $filename);
		$filename = preg_replace("([\.]{2,})", '', $filename);
		$filename = preg_replace("([-]{2,})", '-', $filename);
		$filename = str_replace("-.", '.', $filename);

		return $filename;
	}

}
