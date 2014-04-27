<?php

class ArrayWriter {

	public static function toString($array, $indent=2, $indentchar="\t") {
		if (!is_array($array)) {
			return quote($array);
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
			return $val ? "True" : "False";

		return "\"" . $val . "\"";
	}

}
