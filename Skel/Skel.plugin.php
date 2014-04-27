<?php

class SkelPlugin extends Plugins {

	public function cli($cli) {
		$cli->addCommand("skel", array($this, "init"), "Initialize a new CF project");
	}

	public function init() {
		Cli::copyTree($this->getDir(), getcwd());
		unlink(getcwd() . DIRECTORY_SEPARATOR . basename(__file__));
		$index = file_get_contents(getcwd() . DIRECTORY_SEPARATOR . "index.php");
		$index = str_replace("@CF_DIR@", CF_DIR, $index);
		file_put_contents(getcwd() . DIRECTORY_SEPARATOR . "index.php", $index);
	}

}