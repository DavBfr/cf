<?php

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

		if (is_link($dest))
			unlink($dest);

		if (! file_exists($dest)) {
			symlink($resource, $dest);
		}
	}

}
