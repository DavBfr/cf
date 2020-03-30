<?php namespace DavBfr\CF;
/**
 * Copyright (C) 2013-2016 David PHAM-VAN
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

	/**
	 * @param Cli $cli
	 */
	public function cli($cli) {
		$cli->addCommand("skel:init", array($this, "skel"), "Initialize a new CF project");
		$cli->addCommand("skel:update", array($this, "skelUpdate"), "Update a CF project");
		$cli->addCommand("plugin:create", array($this, "pluginCreate"), "Create a CF plugin");
	}


	/**
	 *
	 */
	protected function updateFiles() {
		Cli::pinfo("Update Files");
		foreach (array("composer.json", "index.php", "setup", "CFApp.plugin.php") as $file) {
			Cli::pinfo(" * Update $file");
			$content = file_get_contents(getcwd() . DIRECTORY_SEPARATOR . $file);
			foreach (array_merge(get_defined_constants(), Options::getAll()) as $key => $val) {
				$content = str_replace("@$key@", $val, $content);
			}
			$content = str_replace("@DATE@", date("r"), $content);
			$gitignore = trim(file_get_contents($this->getDir() . DIRECTORY_SEPARATOR . "project" . DIRECTORY_SEPARATOR . ".gitignore"));
			$content = str_replace("@EXCLUDES@", "\"" . implode("\", \"", explode("\n", $gitignore)) . "\"", $content);
			file_put_contents(getcwd() . DIRECTORY_SEPARATOR . $file, $content);
		}
	}


	/**
	 * @throws \ReflectionException
	 * @throws \Exception
	 */
	public function skel() {
		Cli::enableHelp();
		$dir = opendir(getcwd());
		while (false !== ($file = readdir($dir))) {
			if (($file != '.') && ($file != '..') && ($file != 'data')) {
				Cli::question("The current folder is not empty, do you want to continue?");
				break;
			}
		}
		closedir($dir);
		Cli::pinfo("Create new CF project");
		System::copyTree($this->getDir() . DIRECTORY_SEPARATOR . "project", getcwd());
		$this->updateFiles();
		chmod(getcwd() . DIRECTORY_SEPARATOR . "setup", 0755);
		System::ensureDir(DATA_DIR);
		$conf = Config::getInstance();
		$conf->load(CONFIG_DIR . DIRECTORY_SEPARATOR . "config.json");
		foreach ($conf->get("plugins", array()) as $plugin) {
			Plugins::add($plugin);
		}
		if (DEBUG) {
			foreach ($conf->get("debugPlugins", array()) as $plugin) {
				Plugins::add($plugin);
			}
		}
		Cli::install();
		Options::updateConf(array("DEBUG" => true));
	}


	/**
	 * @throws \Exception
	 */
	public function skelUpdate() {
		Cli::enableHelp();
		Cli::pinfo("Update CF project");
		$srcdir = $this->getDir() . DIRECTORY_SEPARATOR . "project";
		$dstdir = ROOT_DIR;
		foreach (array("index.php", "setup", "README.md", ".htaccess", ".gitignore", "www/index.php", "www/.htaccess") as $file) {
			copy($srcdir . DIRECTORY_SEPARATOR . $file, $dstdir . DIRECTORY_SEPARATOR . $file);
		}
		$this->updateFiles();
		chmod(getcwd() . DIRECTORY_SEPARATOR . "setup", 0755);
		System::ensureDir(DATA_DIR);
		Cli::update();
	}


	/**
	 * @throws \Exception
	 */
	public function pluginCreate() {
		$names = Cli::getInputs('name', 'Plugin name');
		Cli::enableHelp();

		if (count($names) == 0) {
			Cli::pfatal("Missing plugin name");
		}

		foreach ($names as $name) {
			$dest = PLUGINS_DIR . DIRECTORY_SEPARATOR . $name;
			if (file_exists($dest)) {
				if (!Cli::question("A plugin with this name already exists, do you want to replace?")) {
					System::rmtree($dest);
				} else {
					return;
				}
			}

			Cli::pinfo("Create CF plugin '$name'");
			System::copyTree($this->getDir() . DIRECTORY_SEPARATOR . "plugin", $dest);
			rename($dest . DIRECTORY_SEPARATOR . "config" . DIRECTORY_SEPARATOR . "config.json", $dest . DIRECTORY_SEPARATOR . "config" . DIRECTORY_SEPARATOR . "$name.json");
			rename($dest . DIRECTORY_SEPARATOR . "plugin.php", $dest . DIRECTORY_SEPARATOR . "$name.plugin.php");

			foreach (array("composer.json", "$name.plugin.php") as $file) {
				Cli::pinfo(" * Update $file");
				$content = file_get_contents($dest . DIRECTORY_SEPARATOR . $file);
				foreach (Options::getAll() as $key => $val) {
					$content = str_replace("@$key@", $val, $content);
				}
				$content = str_replace("@DATE@", date("r"), $content);
				$content = str_replace("@NAME@", $name, $content);
				file_put_contents($dest . DIRECTORY_SEPARATOR . $file, $content);
			}

			foreach (array("model", "requests", "www", "classes") as $_name) {
				System::ensureDir($dest . DIRECTORY_SEPARATOR . $_name);
			}
		}
	}

}
