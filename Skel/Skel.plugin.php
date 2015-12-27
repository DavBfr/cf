<?php
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

class SkelPlugin extends Plugins {

	public function cli($cli) {
		$cli->addCommand("skel:init", array($this, "skel"), "Initialize a new CF project");
	}

	public function skel() {
		global $configured_options;
		Cli::pinfo("Create new CF project");
		System::copyTree($this->getDir(), getcwd());
		unlink(getcwd() . DIRECTORY_SEPARATOR . basename(__file__));
		foreach(array("composer.json") as $file) {
			Cli::pinfo(" * Update $file");
			$content = file_get_contents(getcwd() . DIRECTORY_SEPARATOR . $file);
			foreach($configured_options as $var) {
				$content = str_replace("@$var@", constant($var), $content);
			}
			file_put_contents(getcwd() . DIRECTORY_SEPARATOR . $file, $content);
		}
		chmod(getcwd() . DIRECTORY_SEPARATOR . "setup", 0755);
		System::ensureDir(DATA_DIR);
		$conf = Config::getInstance();
		$conf->load(CONFIG_DIR . DIRECTORY_SEPARATOR . "config.json");
		foreach($conf->get("plugins", Array()) as $plugin) {
			Plugins::add($plugin);
		}
		Cli::install();
		Crud::create(array("input"=>array("", "", "user")));
	}

}
