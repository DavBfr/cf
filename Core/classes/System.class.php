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
			symlink($resource, $dest);
		}
	}


	public static function rmtree($path) {
		if (is_dir($path)) {
			foreach (glob("{$path}/*") as $file) {
				if (is_dir($file)) {
					self::rmtree($file);
				} else {
					Logger::debug("delete $file");
					unlink($file);
				}
			}
			rmdir($path);
		}
	}

}
