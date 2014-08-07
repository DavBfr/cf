<?php

class BootstrapPlugin extends Plugins {

	public function install() {
		System::publish($this->getDir() . "/www/vendor/fonts");
	}

}
