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
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.	See the GNU
 * Lesser General Public License for more details.
 *
 * You should have received a copy of the GNU Lesser General Public
 * License along with this library; if not, write to the Free Software
 * Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA	02110-1301, USA.
 **/

class System {
	private static $relative = false;


	public static function setRelativePublish($rel) {
		self::$relative = $rel;
	}


	public static function ensureDir($name, $mode = 0750) {
		if (! is_dir($name)) {
			if (! mkdir($name, $mode, true)) {
				ErrorHandler::error(500, null, "Unable to create directory $name");
			}
		}
	}


	public static function publish($resource, $dest = null) {
		if ($dest === null)
			$dest = WWW_DIR . DIRECTORY_SEPARATOR . basename($resource);
		else
			$dest = WWW_DIR . DIRECTORY_SEPARATOR . $dest;

		$resource = self::absPath($resource);
		Logger::debug("publish $resource => $dest");

		if (is_link($dest))
			unlink($dest);

		if (! file_exists($dest)) {
			self::symlink($resource, $dest, self::$relative);
		}
	}


	public static function absPath($path) {
		$out = array();
		foreach(explode(DIRECTORY_SEPARATOR, $path) as $i => $fold) {
				if ($fold == '' || $fold == '.') continue;
				if ($fold == '..' && $i > 0 && end($out) != '..') array_pop($out);
		else $out[] = $fold;
		} return ($path{0} == DIRECTORY_SEPARATOR ? DIRECTORY_SEPARATOR : '') . join(DIRECTORY_SEPARATOR, $out);
	}


	public static function relativePath($from, $to) {
		$arFrom = explode(DIRECTORY_SEPARATOR, rtrim($from, DIRECTORY_SEPARATOR));
		$arTo = explode(DIRECTORY_SEPARATOR, rtrim($to, DIRECTORY_SEPARATOR));
		while(count($arFrom) && count($arTo) && ($arFrom[0] == $arTo[0])) {
			array_shift($arFrom);
			array_shift($arTo);
		}
		$p = array();
		Logger::Debug(print_r($arFrom, true));
		if (count($arFrom) > 0)
			$p = array_fill(0, count($arFrom), '..');
		$p = array_merge($p, $arTo);
		return implode(DIRECTORY_SEPARATOR, $p);
	}


	public static function relsymlink($src, $dst) {
		$rsrc = self::relativePath(dirname(self::absPath($dst)), self::absPath($src));
		return @symlink($rsrc, $dst);
	}


	public static function symlink($src, $dst, $relative = true) {
		if (substr($src, 0, 7) == "phar://" || ($relative && self::relsymlink($src, $dst) === false) || (!$relative && @symlink($src, $dst) === false)) {
			if (is_dir($src))
				self::copyTree($src, $dst);
			else
				copy($src, $dst);
		}
	}


	public static function copyTree($src, $dst) {
		$dir = opendir($src);
		@mkdir($dst);
		while(false !== ($file = readdir($dir))) {
			if (($file != '.') && ($file != '..')) {
				if (is_dir($src . DIRECTORY_SEPARATOR . $file)) {
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


	public function highlightCode($text) {
    $text = highlight_string("<?php " . $text, true);
		$text = trim($text);
    $text = preg_replace("|^\\<code\\>\\<span style\\=\"color\\: #[a-fA-F0-9]{0,6}\"\\>|", "", $text, 1);
    $text = preg_replace("|\\</code\\>\$|", "", $text, 1);
    $text = trim($text);
    $text = preg_replace("|\\</span\\>\$|", "", $text, 1);
    $text = trim($text);
    $text = preg_replace("|^(\\<span style\\=\"color\\: #[a-fA-F0-9]{0,6}\"\\>)(&lt;\\?php&nbsp;)(.*?)(\\</span\\>)|", "\$1\$3\$4", $text);

    return $text;
	}

}
