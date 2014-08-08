<?php

class BootstrapPlugin extends Plugins {

	public function update() {
		Cli::pln(" * install fonts");
		System::publish($this->getDir() . "/www/vendor/fonts");
	}

}
