<?php

class System {
	
	public static function ensureDir($name, $mode=0750) {
		if (! is_dir($name)) {
			if (! mkdir($name, $mode, true)) {
				ErrorHandler::error(500, NULL, "Unable to create directory $name");
			}
		}
	}

}
