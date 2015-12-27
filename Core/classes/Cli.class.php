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

class Cli {
	const ansiraz = "\e[0m";
	const ansierr = "\e[0;31m";
	const ansiinfo = "\e[1;32m";
	const ansidebug = "\e[1;34m";
	const ansilog = "\e[0;36m";
	const ansiwarn = "\e[1;35m";
	const ansilerr = "\e[1;31m";
	const ansicrit = "\e[0;35m";
	
	private $args;
	private $commands;
	private static $has_colors;

	public function __construct($argv) {
		while (ob_get_level())
			ob_end_clean();

		self::$has_colors = posix_isatty(STDOUT);
		$this->args = $this->parseArguments($argv);
		$logger = Logger::getInstance();
		if (array_key_exists("v", $this->args))
			$logger->setLevel(Logger::DEBUG);

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
		self::perr("Unknown command $command");
		$this->printHelp($params);
	}


	private function printHelpOption($name, $desc) {
		self::pcolor(self::ansicrit, "  $name");
		self::pcolorln(self::ansilog, " : $desc");
	}


	public function printHelp($args) {
		self::pcolorln(self::ansiinfo, "Help " . basename($args["input"][0]) . " <command> [options]");
		self::pln();
		self::pcolorln(self::ansiinfo, "Commands:");
		foreach ($this->commands as $name => $value) {
			$this->printHelpOption($name, $value[1]);
		}
		self::pln();
		self::pcolorln(self::ansiinfo, "Options:");
		$this->printHelpOption("-v", "verbose output");
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
		fwrite(STDOUT, $s);
		fflush(STDOUT);
	}


	public static function pln($s="") {
		self::pr($s . PHP_EOL);
	}


	public static function pcolor($color, $s) {
		if (self::$has_colors)
			self::pr($color . $s . self::ansiraz);
		else
			self::pr($s);
	}


	public static function pcolorln($color, $s) {
		self::pcolor($color, $s . PHP_EOL);
	}


	public static function perr($s="") {
		self::pcolorln(self::ansierr, $s);
	}


	public static function pfatal($s="") {
		self::pcolorln(self::ansierr, $s);
		die(-1);
	}


	public static function pinfo($s="") {
		self::pcolorln(self::ansiinfo, $s);
	}


	public static function plog($level, $data) {
		switch ($level) {
			case Logger::DEBUG:
				self::pcolorln(self::ansidebug, $data);
				break;
			case Logger::INFO:
				self::pcolorln(self::ansilog, $data);
				break;
			case Logger::WARNING:
				self::pcolorln(self::ansiwarn, $data);
				break;
			case Logger::ERROR:
				self::pcolorln(self::ansilerr, $data);
				break;
			case Logger::CRITICAL:
				self::pcolorln(self::ansicrit, $data);
				break;
		}
	}


	public static function question($s="") {
		self::pinfo($s);
		self::perr("Type 'yes' to continue: ");
		$handle = fopen ("php://stdin","r");
		$line = fgets($handle);
		if(trim($line) != 'yes'){
			self::pfatal("ABORTING!");
		}
		self::pln();
		self::pinfo("Thank you, continuing...");
	}


	public static function configuration() {
		global $configured_options;

		if (isset($configured_options)) {
			foreach($configured_options as $key) {
				$val = constant($key);
				if (is_bool($val))
					$val = $val?"true":"false";
				
				self::pln($key.' = '.$val);
			}
		}
	}


	public static function jconfig() {
		$conf = Config::getInstance();
		$data = $conf->getData();
		self::pln(json_encode($data, JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE));
	}


	public static function version() {
		self::pln(CorePlugin::getBaseline());
	}


	public static function install() {
		self::pinfo("Installing the application");
		Plugins::dispatchAllReversed("preinstall");
		Plugins::dispatchAllReversed("preupdate");
		Plugins::dispatchAllReversed("install");
		Plugins::dispatchAll("postinstall");
		self::update();
	}


	public static function update() {
		self::pinfo("Updating the application");
		Plugins::dispatchAllReversed("preupdate");
		Plugins::dispatchAllReversed("update");
		Plugins::dispatchAll("postupdate");

		$content = "<?php // DO NOT MODIFY THIS FILE, IT IS GENERATED BY setup update SCRIPT\n\n";
		$content .= "define(\"CF_DIR\", \"".CF_DIR."\");\n";
		$content .= "define(\"ROOT_DIR\", \"".ROOT_DIR."\");\n";
		file_put_contents(CONFIG_DIR . "/paths.php", $content);
	}


	public static function clean() {
		self::pinfo("Clean the application cache");
		System::rmtree(CACHE_DIR);
		System::rmtree(WWW_CACHE_DIR);
	}

}
