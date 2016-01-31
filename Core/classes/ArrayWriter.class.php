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

class ArrayWriter {

	public static function toFile($array, $filename) {
		file_put_contents($filename, "<?php\nreturn " . self::toString($array) . ";");
	}


	public static function fromFile($filename) {
		return include $filename;
	}


	public static function toString($array, $indent=2, $indentchar="\t") {
		return var_export($array, true);

		if (!is_array($array)) {
			return self::quote($array);
		}

		$strings=array();
		foreach ($array as $ind => $val) {
			$strings[] = self::quote($ind) . "=>" . (is_array($val) ? self::toString($val, $indent+1, $indentchar) : self::quote($val));
		}

		$i = str_repeat($indentchar, $indent);
		return "array(\n$i" . implode(",\n$i", $strings) . ")";
	}


	public static function quote($val) {
		if (is_int($val))
			return $val;

		if (is_bool($val))
			return $val ? "true" : "false";

		return "'" . preg_quote((string)$val, "'") . "'";
	}

}
