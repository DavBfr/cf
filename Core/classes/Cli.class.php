<?php
/**
 * Copyright (C) 2013 David PHAM-VAN
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License
 * as published by the Free Software Foundation; version 2
 * of the License.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301, USA.
 **/

class Cli {
	private $args;
	private $commands;

	public function __construct($argv) {
		while (ob_get_level())
			ob_end_clean();

		$this->args = $this->parseArguments($argv);
		$this->commands = array();
		$this->addCommand("help", array($this, "printHelp"), "Help messages");
	}


	public function getCommand() {
		if (isset($this->args['input']) && isset($this->args['input'][1]))
			return $this->args['input'][1];
		return "help";
	}


	public function getArguments() {
		return $this->args;
	}


	public function addCommand($name, $callable, $help) {
		$this->commands[$name] = array($callable, $help);
	}


	public function handle($command, $params) {
		if (isset($this->commands[$command])) {
			return call_user_func($this->commands[$command][0], $params);
		}
		self::pln("Unknown command $command");
		$this->printHelp($params);
	}


	public function printHelp($args) {
		self::pln("Help " . $args["input"][0]);
		foreach ($this->commands as $name => $value) {
			self::pln("  $name : " . $value[1]);
		}
	}


	private function parseArguments($argv) {
		$_ARG = array();
		foreach ($argv as $arg) {
			if (preg_match('#^-{1,2}([a-zA-Z0-9]*)=?(.*)$#', $arg, $matches)) {
				$key = $matches[1];
				switch ($matches[2])
				{
					case '':
					case 'true':
					case 'yes':
						$arg = true;
						break;
					case 'false':
					case '0':
					case 'no':
						$arg = false;
						break;
					default:
						$arg = $matches[2];
				}
				$_ARG[$key] = $arg;
			} else {
				$_ARG['input'][] = $arg;
			}
		}
		return $_ARG;
	}


	public static function pr($s="") {
		print($s);
		flush();
	}


	public static function pln($s="") {
		self::pr($s . "\n");
	}


	public static function question() {
		echo "Are you sure you want to do this?  Type 'yes' to continue: ";
		$handle = fopen ("php://stdin","r");
		$line = fgets($handle);
		if(trim($line) != 'yes'){
			echo "ABORTING!\n";
			exit;
		}
		echo "\n";
		echo "Thank you, continuing...\n";
	}


	public static function copyTree($src, $dst) {
		$dir = opendir($src);
		@mkdir($dst);
		while(false !== ( $file = readdir($dir)) ) {
			if (( $file != '.' ) && ( $file != '..' )) {
				if ( is_dir($src . DIRECTORY_SEPARATOR . $file) ) {
					self::copyTree($src . DIRECTORY_SEPARATOR . $file, $dst . DIRECTORY_SEPARATOR . $file);
				}
				else {
					copy($src . DIRECTORY_SEPARATOR . $file, $dst . DIRECTORY_SEPARATOR . $file);
				}
			}
		}
		closedir($dir);
	}


	public static function configuration() {
		global $configured_options;

		
		if (isset($configured_options)) {
			foreach($configured_options as $key) {
				$val = constant($key);
				self::pln($key.' = '.$val);
			}
		}
	}


	public static function version() {
		self::pln(CorePlugin::getBaseline());
	}


	public static function install() {
		self::pln("Installing the application");
		Plugins::dispatchAllReversed("preinstall");
		Plugins::dispatchAllReversed("preupdate");
		Plugins::dispatchAllReversed("install");
		Plugins::dispatchAllReversed("update");
	}


	public static function update() {
		self::pln("Updating the application");
		Plugins::dispatchAllReversed("preupdate");
		Plugins::dispatchAllReversed("update");
	}

}
