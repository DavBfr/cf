<?php namespace DavBfr\CF;
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

use Phar;
use FilesystemIterator;
use BadMethodCallException;

class Cli {
	const ansiraz = "\e[0m";
	const ansierr = "\e[0;31m";
	const ansiinfo = "\e[1;32m";
	const ansidebug = "\e[1;34m";
	const ansilog = "\e[0;36m";
	const ansiwarn = "\e[1;35m";
	const ansilerr = "\e[1;31m";
	const ansicrit = "\e[0;35m";

	protected $args;
	protected $commands;
	protected $options;
	protected $switches;
	protected $inputs;
	protected $has_colors;
	private static $instance = NULL;

	public function __construct($argv) {
		self::$instance = $this;

		while (ob_get_level())
			ob_end_clean();

		$this->has_colors = posix_isatty(STDOUT);
		$this->args = $this->parseArguments($argv);
		$logger = Logger::getInstance();
		if (array_key_exists("v", $this->args))
			$logger->setLevel(Logger::DEBUG);

		$this->commands = array();
		$this->options = array();
		$this->switches = array();
		$this->inputs = array();
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
		$found = NULL;
		foreach($this->commands as $key => $val) {
			if (strpos($key, $command) !== false) {
				if ($found === NULL) {
					$found = $val;
				} else {
					$found = false;
				}
			}
		}
		if ($found !== NULL && $found !== false) {
			return call_user_func($found[0], $params);
		}
		self::perr("Unknown command $command");
		$this->printHelp($params);
	}


	private static function printHelpOption($name, $desc) {
		self::pcolor(self::ansicrit, "	$name");
		self::pcolorln(self::ansilog, " : $desc");
	}


	public function printHelp($args) {
		self::pcolorln(self::ansiinfo, "Help " . basename($args["input"][0]) . " <command> [options]");
		self::pln();
		self::pcolorln(self::ansiinfo, "Commands:");
		foreach ($this->commands as $name => $value) {
			self::printHelpOption($name, $value[1]);
		}
		self::pln();
		self::pcolorln(self::ansiinfo, "Options:");
		self::printHelpOption("-v", "verbose output");
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


	public static function addSwitch($name, $help) {
		if (array_key_exists($name, self::$instance->options) || array_key_exists($name, self::$instance->switches)) {
			self::pfatal("The command line option $name is already registered");
		}
		self::$instance->switches[$name] = $help;
		if (array_key_exists($name, self::$instance->args)) {
			return self::$instance->args[$name];
		}
		return false;
	}


	public static function addOption($name, $defaultvalue, $help) {
		if (array_key_exists($name, self::$instance->options) || array_key_exists($name, self::$instance->switches)) {
			self::pfatal("The command line option $name is already registered");
		}
		self::$instance->options[$name] = $help;
		if (array_key_exists($name, self::$instance->args)) {
			return self::$instance->args[$name];
		}
		return $defaultvalue;
	}
	
	
	public static function getInputs($name, $help) {
		self::$instance->inputs[$name] = $help;
		return array_slice(self::$instance->args["input"], 2);
	}


	public static function enableHelp() {
		if (!IS_CLI)
			return;

		$help = self::addSwitch("h", "This help");
		self::addSwitch("v", "Verbose output");
		$merged = array_merge(array_keys(self::$instance->switches), array_keys(self::$instance->options));
		foreach(self::$instance->args as $key => $val) {
			if ($key != "input" && array_search($key, $merged) === false) {
				$help = true;
				self::perr("Command line option -$key=$val doesn't exists");
				break;
			}
		}
		if (!$help && count(self::$instance->inputs) > 0 && count(self::$instance->args["input"]) == 2) {
			$help = true;
			self::perr("Missing command line arguments");
		}
		if ($help !== false) {
			$helpmsg = "Help " . basename(self::$instance->args["input"][0]) . " " . self::$instance->args["input"][1] . " [options]";
			foreach(self::$instance->inputs as $key => $val) {
				$helpmsg .= " <$key>";
			}
			self::pcolorln(self::ansiinfo, $helpmsg);
			self::pln();
			self::pcolorln(self::ansiinfo, "  " . self::$instance->commands[self::$instance->args["input"][1]][1]);
			foreach(self::$instance->inputs as $key => $val) {
				self::printHelpOption($key, $val);
			}
			self::pln();
			self::pcolorln(self::ansiinfo, "Options:");
			foreach(self::$instance->switches as $key=>$val) {
				self::printHelpOption("-" . $key, $val);
			}
			foreach(self::$instance->options as $key=>$val) {
				self::printHelpOption("-" . $key . "=" . strtoupper($key), $val);
			}
			Output::finish();
		}
		self::$instance->options = array();
		self::$instance->switches = array();
		self::$instance->inputs = array();
	}


	private static function static_output($color, $s) {
		fwrite(STDOUT, $s);
		fflush(STDOUT);
	}


	public function output($color, $s) {
		if ($this->has_colors && $color)
			fwrite(STDOUT, $color . $s . self::ansiraz);
		else
			fwrite(STDOUT, $s);
		fflush(STDOUT);
	}


	public static function pr($s="") {
		if (self::$instance)
			self::$instance->output(false, $s);
		else
			self::static_output(false, $s);
	}


	public static function pln($s="") {
		self::pr($s . PHP_EOL);
	}


	public static function pcolor($color, $s) {
		if (self::$instance)
			self::$instance->output($color, $s);
		else
			self::static_output($color, $s);
	}


	public static function pcolorln($color, $s) {
		self::pcolor($color, $s . PHP_EOL);
	}


	public static function perr($s="") {
		self::pcolorln(self::ansierr, $s);
	}


	public static function pfatal($s="") {
		self::pcolorln(self::ansierr, $s);
		Output::finish(-1);
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
		
		Cli::enableHelp();

		if (isset($configured_options)) {
			foreach($configured_options as $key) {
				$val = constant($key);
				if (is_bool($val))
					$val = $val?"true":"false";

				self::pln($key.' = '.$val);
			}
		}
	}


	public static function exportconf() {
		global $configured_options;
		
		Cli::enableHelp();

		$ex = array("CF_VERSION", "INIT_CONFIG_DIR", "CF_DIR", "ROOT_DIR", "CORE_PLUGIN", "CF_URL", "IS_CLI", "DOCUMENT_ROOT", "CF_PLUGINS_DIR", "WWW_PATH");

		self::pln("<?php namespace DavBfr\CF;");
		if (isset($configured_options)) {
			$sopts=$configured_options;
			asort($sopts);
			if (substr(WWW_PATH, 0, strlen(ROOT_DIR)) == ROOT_DIR) {
				$val = "\"" . substr(WWW_PATH, strlen(ROOT_DIR)) . "\"";
				self::pln("configure(\"WWW_PATH\", $val);");
			} else {
				self::pln("configure(\"WWW_PATH\", \"".WWW_PATH."\");");
			}
			foreach($sopts as $key) {
				if (array_search($key, $ex) === false) {
					$val = constant($key);
					if (strpos($key, "_DIR") !== false) {
						if (substr($val, 0, strlen(ROOT_DIR)) == ROOT_DIR) {
							$val = "ROOT_DIR . \"" . substr($val, strlen(ROOT_DIR)) . "\"";
						} else if (substr($val, 0, strlen(CF_DIR)) == CF_DIR) {
							$val = "CF_DIR . \"" . substr($val, strlen(CF_DIR)) . "\"";
						}
					} else if (strpos($key, "_PATH") !== false) {
							if (substr($val, 0, strlen(WWW_PATH)) == WWW_PATH) {
								$val = "WWW_PATH . \"" . substr($val, strlen(WWW_PATH)) . "\"";
							}
					} else if (is_bool($val))
						$val = $val?"true":"false";
					else if (is_int($val))
							$val = $val;
					else if (is_string($val))
						$val = "\"$val\"";
					self::pln("configure(\"$key\", $val);");
				}
			}
		}
	}


	public static function jconfig() {
		Cli::enableHelp();
		$conf = Config::getInstance();
		$data = $conf->getData();
		$p = 0;
		if (version_compare(PHP_VERSION, '5.4.0') >= 0)
			$p = JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES|JSON_UNESCAPED_UNICODE;
		self::pln(json_encode($data, $p));
	}


	public static function version() {
		Cli::enableHelp();
		self::pln(CorePlugin::getBaseline());
	}


	public static function install() {
		Cli::enableHelp();
		self::pinfo("Installing the application");
		Plugins::dispatchAllReversed("preinstall");
		Plugins::dispatchAllReversed("preupdate");
		Plugins::dispatchAllReversed("install");
		Plugins::dispatchAll("postinstall");
		self::update();
	}


	public static function update() {
		Cli::enableHelp();
		self::pinfo("Updating the application");
		Plugins::dispatchAllReversed("preupdate");
		Plugins::dispatchAllReversed("update");
		Plugins::dispatchAll("postupdate");
		self::clean();

		$content = "<?php // DO NOT MODIFY THIS FILE, IT IS GENERATED BY setup update SCRIPT\n\n";
		$content .= "define(\"CF_DIR\", \"".CF_DIR."\");\n";
		$content .= "define(\"ROOT_DIR\", \"".ROOT_DIR."\");\n";
		file_put_contents(CONFIG_DIR . "/paths.php", $content);
	}


	public static function clean() {
		Cli::enableHelp();
		self::pinfo("Clean the application cache");
		System::rmtree(CACHE_DIR);
		System::rmtree(WWW_CACHE_DIR);
	}


	private static function addToPhar($phar, $dir) {
		$subdirs = array();
		if ($dh = opendir($dir)) {
			while (($file = readdir($dh)) !== false) {
				if ($file[0] != "." && $file != "tests") {
					$filename = $dir . "/" . $file;
					if (is_dir($filename)) {
						$subdirs[] = $filename;
					} else {
						$relfilename = substr($filename, strlen(CF_DIR)+1);
						Cli::pcolorln(self::ansiwarn, "Add $relfilename");
						$phar->addFile($filename, $relfilename);
					}
				}
			}
			closedir($dh);
		}

		foreach($subdirs as $filename) {
			self::addToPhar($phar, $filename);
		}
	}


	public static function phar() {
		/* Build with:
			php --define phar.readonly=0 cf/setup.php core:phar
		*/
		Cli::enableHelp();
		$pharname = "cf-".CF_VERSION.".phar";
		$pharfile = ROOT_DIR . DIRECTORY_SEPARATOR . $pharname;
		if (file_exists($pharfile))
			unlink($pharfile);
		$phar = new Phar(
			$pharfile,
			FilesystemIterator::CURRENT_AS_FILEINFO | FilesystemIterator::KEY_AS_FILENAME,
			$pharname
		);
		$phar->startBuffering();
		$phar->setSignatureAlgorithm(Phar::SHA256);
		self::addToPhar($phar, CF_DIR);
		$phar->setStub($phar->createDefaultStub("setup.php"));
		$phar->compressFiles(Phar::GZ);
		$phar->stopBuffering();
		Cli::pinfo("Phar archive " . ROOT_DIR . DIRECTORY_SEPARATOR . $pharname . " has been saved.");
	}


}
