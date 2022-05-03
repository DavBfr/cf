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

use FilesystemIterator;
use Phar;

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
	protected $inputs_offset;
	protected $has_colors;
	private static $instance = null;


	/**
	 * Cli constructor.
	 * @param array $argv
	 */
	public function __construct($argv) {
		self::$instance = $this;

		while (ob_get_level())
			ob_end_clean();

		$this->has_colors = defined('STDOUT') ? posix_isatty(STDOUT) : false;
		$this->args = $this->parseArguments($argv);
		$logger = Logger::getInstance();
		if (array_key_exists("v", $this->args))
			$logger->setLevel(Logger::DEBUG);

		$this->commands = array();
		$this->options = array();
		$this->switches = array();
		$this->inputs = array();
		$this->inputs_offset = 2;
		$this->addCommand("help", array($this, "printHelp"), "Help messages");
	}


	/**
	 * @return string
	 */
	public function getCommand() {
		if (isset($this->args['input']) && isset($this->args['input'][1]))
			return $this->args['input'][1];
		return "help";
	}


	/**
	 * @return array
	 */
	public function getArguments() {
		return $this->args;
	}


	/**
	 * @param string $name
	 * @param callable $callable
	 * @param string $help
	 */
	public function addCommand($name, $callable, $help) {
		$this->commands[$name] = array($callable, $help);
	}


	/**
	 * @param string $command
	 * @param array $params
	 * @return bool|null
	 */
	public function handle($command, $params) {
		if (isset($this->commands[$command])) {
			return call_user_func($this->commands[$command][0], $params);
		}
		$found = null;
		foreach ($this->commands as $key => $val) {
			if (strpos($key, $command) !== false) {
				if ($found === null) {
					$found = $val;
				} else {
					$found = false;
				}
			}
		}
		if ($found !== null && $found !== false) {
			return call_user_func($found[0], $params);
		}
		self::perr("Unknown command $command");
		$this->printHelp($params);

		return null;
	}


	/**
	 * @param string $name
	 * @param string $desc
	 */
	private static function printHelpOption($name, $desc) {
		self::pcolor(self::ansicrit, "	$name");
		self::pcolorln(self::ansilog, " : $desc");
	}


	/**
	 * @param array $args
	 */
	public function printHelp($args) {
		self::pcolorln(self::ansiinfo, "Help " . basename(isset($args["input"]) ? $args["input"][0] : './setup') . " <command> [options]");
		self::pln();
		self::pcolorln(self::ansiinfo, "Commands:");
		foreach ($this->commands as $name => $value) {
			self::printHelpOption($name, $value[1]);
		}
		self::pln();
		self::pcolorln(self::ansiinfo, "Options:");
		self::printHelpOption("-v", "verbose output");
	}


	/**
	 * @param array $argv
	 * @return array
	 */
	private function parseArguments($argv) {
		$_ARG = array();
		foreach ($argv as $arg) {
			if (preg_match('#^-{1,2}([a-zA-Z0-9]*)=?(.*)$#', $arg, $matches)) {
				$key = $matches[1];
				switch ($matches[2]) {
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


	/**
	 * @param string $name
	 * @param bool $defaultValue
	 * @return bool
	 */
	public static function getOption($name, $defaultValue = false) {
		if (array_key_exists($name, self::$instance->args)) {
			return self::$instance->args[$name];
		}
		return $defaultValue;
	}


	/**
	 * @param string $name
	 * @param string $help
	 * @return bool
	 */
	public static function addSwitch($name, $help) {
		if (array_key_exists($name, self::$instance->options) || array_key_exists($name, self::$instance->switches)) {
			self::pfatal("The command line option $name is already registered");
		}
		self::$instance->switches[$name] = $help;
		return self::getOption($name);
	}


	/**
	 * @param string $name
	 * @param string $defaultValue
	 * @param string $help
	 * @return bool
	 */
	public static function addOption($name, $defaultValue, $help) {
		if (array_key_exists($name, self::$instance->options) || array_key_exists($name, self::$instance->switches)) {
			self::pfatal("The command line option $name is already registered");
		}
		self::$instance->options[$name] = $help;
		return self::getOption($name, $defaultValue);
	}


	/**
	 * @param string $name
	 * @param string $help
	 * @param bool $optional
	 * @param int|null $count
	 * @return array
	 * @throws \Exception
	 */
	public static function getInputs($name, $help, $optional = false, $count = null) {
		if (self::$instance->inputs_offset === false) {
			ErrorHandler::error(500, 'Impossible to have input parameter after catch-all input', $name);
		}

		self::$instance->inputs[$name] = array($optional, $count, self::$instance->inputs_offset, $help);
		$ret = array_slice(self::$instance->args["input"], self::$instance->inputs_offset, $count);

		if ($count !== null)
			self::$instance->inputs_offset += $count;
		else
			self::$instance->inputs_offset = false;

		return $ret;
	}


	/**
	 *
	 */
	public static function enableHelp() {
		if (!IS_CLI)
			return;

		$help = self::addSwitch("h", "This help");
		self::addSwitch("v", "Verbose output");
		$merged = array_merge(array_keys(self::$instance->switches), array_keys(self::$instance->options));
		foreach (self::$instance->args as $key => $val) {
			if ($key != "input" && array_search($key, $merged) === false) {
				$help = true;
				self::perr("Command line option -$key=$val doesn't exists");
				break;
			}
		}
		if (!$help) {
			foreach (self::$instance->inputs as $name => $val) {
				if (!$val[0] && count(self::$instance->args["input"]) < $val[2] + ($val[1] === null ? 1 : $val[1])) {
					$help = true;
					self::perr("Missing command line arguments");
					break;
				}
			}
		}
		if ($help !== false) {
			$helpMsg = "Help " . basename(self::$instance->args["input"][0]) . " " . self::$instance->args["input"][1] . " [options]";
			foreach (self::$instance->inputs as $key => $val) {
				$o = $val[0] ? '[' : '<';
				$c = $val[0] ? ']' : '>';
				if ($val[1] !== null)
					$helpMsg .= str_repeat(" $o$key$c", $val[1]);
				else
					$helpMsg .= " $o$key...$c";
			}

			self::pcolorln(self::ansiinfo, $helpMsg);
			self::pln();
			self::pcolorln(self::ansiinfo, "  " . self::$instance->commands[self::$instance->args["input"][1]][1]);
			foreach (self::$instance->inputs as $key => $val) {
				self::printHelpOption($key, $val[3]);
			}
			self::pln();
			self::pcolorln(self::ansiinfo, "  Options:");
			foreach (self::$instance->switches as $key => $val) {
				self::printHelpOption("-" . $key, $val);
			}
			foreach (self::$instance->options as $key => $val) {
				self::printHelpOption("-" . $key . "=" . strtoupper($key), $val);
			}
			Output::finish();
		}
		self::$instance->options = array();
		self::$instance->switches = array();
		self::$instance->inputs = array();
	}


	/**
	 * @param $color
	 * @param $s
	 */
	private static function static_output($color, $s) {
		fwrite(STDOUT, $s);
		fflush(STDOUT);
	}


	/**
	 * @param string $color
	 * @param string $s
	 */
	public function output($color, $s) {
		if ($this->has_colors && $color)
			fwrite(STDOUT, $color . $s . self::ansiraz);
		else
			fwrite(STDOUT, $s);
		fflush(STDOUT);
	}


	/**
	 * @param string $s
	 */
	public static function pr($s = "") {
		if (self::$instance)
			self::$instance->output(false, $s);
		else
			self::static_output(false, $s);
	}


	/**
	 * @param string $s
	 */
	public static function pln($s = "") {
		self::pr($s . PHP_EOL);
	}


	/**
	 * @param string $color
	 * @param string $s
	 */
	public static function pcolor($color, $s) {
		if (self::$instance)
			self::$instance->output($color, $s);
		else
			self::static_output($color, $s);
	}


	/**
	 * @param string $color
	 * @param string $s
	 */
	public static function pcolorln($color, $s) {
		self::pcolor($color, $s . PHP_EOL);
	}


	/**
	 * @param string $s
	 */
	public static function perr($s = "") {
		self::pcolorln(self::ansierr, $s);
	}


	/**
	 * @param string $s
	 */
	public static function pfatal($s = "") {
		self::pcolorln(self::ansierr, $s);
		Output::finish(-1);
	}


	/**
	 * @param string $s
	 */
	public static function pinfo($s = "") {
		self::pcolorln(self::ansiinfo, $s);
	}


	/**
	 * @param int $level
	 * @param string $data
	 */
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


	/**
	 * @param string $s
	 * @return bool
	 */
	public static function question($s = "") {
		if (!IS_CLI)
			return false;

		self::pinfo($s);
		self::perr("Type 'yes' to continue: ");
		$handle = fopen("php://stdin", "r");
		$line = fgets($handle);
		if (trim($line) != 'yes') {
			self::pfatal("ABORTING!");
			return false;
		}
		self::pln();
		self::pinfo("Thank you, continuing...");
		return true;
	}


	/**
	 *
	 * @throws \Exception
	 */
	public static function configuration() {
		$set = self::addSwitch("set", "Set an option");
		$global = self::addSwitch("global", "Set the option in global configuration");
		$get = self::addSwitch("get", "Get an option");
		if ($set || $get) {
			$keys = self::getInputs("key", "Key and value to set or search");
		} else $keys = array();
		self::enableHelp();

		if ($set) {
			if (count($keys) > 1) {
				$val = $keys[1];
			} else
				$val = null;
			if (strtolower($val) == "true")
				$val = true;
			elseif (strtolower($val) == "false")
				$val = false;
			elseif ((string)((int)$val) == $val)
				$val = (int)$val;
			Options::updateConf(array(strtoupper($keys[0]) => $val), !$global);
		} else {
			$opts = Options::getAll();
			ksort($opts);
			foreach ($opts as $key => $val) {
				if (!$get || strtoupper($key) == strtoupper($keys[0])) {
					if (is_bool($val))
						$val = $val ? "true" : "false";
					self::pln($key . ' = ' . $val);
				}
			}
		}
	}


	/**
	 *
	 */
	public static function jconfig() {
		self::enableHelp();
		$conf = Config::getInstance();
		$data = $conf->getData();
		$p = 0;
		if (version_compare(PHP_VERSION, '5.4.0') >= 0)
			$p = JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE;
		self::pln(json_encode($data, $p));
	}


	/**
	 *
	 */
	public static function version() {
		self::enableHelp();
		self::pln(CorePlugin::getBaseline());
	}


	/**
	 *
	 */
	public static function install() {
		System::setRelativePublish(!self::addSwitch("a", "Generate absolute paths"));
		self::enableHelp();
		self::pinfo("Installing the application");
		Plugins::dispatchAllReversed("preinstall");
		Plugins::dispatchAllReversed("preupdate");
		Plugins::dispatchAllReversed("install");
		Plugins::dispatchAll("postinstall");
		Plugins::dispatchAllReversed("preupdate");
		Plugins::dispatchAllReversed("update");
		Plugins::dispatchAll("postupdate");
		Plugins::dispatchAll("clean");
	}


	/**
	 *
	 */
	public static function update() {
		System::setRelativePublish(!self::addSwitch("a", "Generate absolute paths"));
		self::enableHelp();
		self::pinfo("Updating the application");
		Plugins::dispatchAllReversed("preupdate");
		Plugins::dispatchAllReversed("update");
		Plugins::dispatchAll("postupdate");
		Plugins::dispatchAll("clean");
	}


	/**
	 *
	 */
	public static function clean() {
		self::enableHelp();
		self::pinfo("Clean the application cache");
		Plugins::dispatchAll("clean");
	}


	/**
	 * @param Phar $phar
	 * @param string $dir
	 */
	private static function addToPhar($phar, $dir) {
		$subDirs = array();
		if ($dh = opendir($dir)) {
			while (($file = readdir($dh)) !== false) {
				if ($file[0] != "." && $file != "tests") {
					$filename = $dir . "/" . $file;
					if (is_dir($filename)) {
						$subDirs[] = $filename;
					} else {
						$relFilename = substr($filename, strlen(Options::get('CF_DIR')) + 1);
						self::pcolorln(self::ansiwarn, "Add $relFilename");
						$phar->addFile($filename, $relFilename);
					}
				}
			}
			closedir($dh);
		}

		foreach ($subDirs as $filename) {
			self::addToPhar($phar, $filename);
		}
	}


	/**
	 *
	 */
	public static function phar() {
		/* Build with:
			php --define phar.readonly=0 cf/setup.php core:phar
		*/
		self::enableHelp();
		$pharName = "cf-" . CF_VERSION . ".phar";
		$pharFile = ROOT_DIR . DIRECTORY_SEPARATOR . $pharName;
		if (file_exists($pharFile))
			unlink($pharFile);
		$phar = new Phar(
			$pharFile,
			FilesystemIterator::CURRENT_AS_FILEINFO | FilesystemIterator::KEY_AS_FILENAME,
			$pharName
		);
		$phar->startBuffering();
		$phar->setSignatureAlgorithm(Phar::SHA256);
		self::addToPhar($phar, Options::get('CF_DIR'));
		$phar->setStub($phar->createDefaultStub("setup.php"));
		$phar->compressFiles(Phar::GZ);
		$phar->stopBuffering();
		self::pinfo("Phar archive " . ROOT_DIR . DIRECTORY_SEPARATOR . $pharName . " has been saved.");
	}


}
