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

use Exception;


class Plugins {
	const CLASS_DIR = "classes";
	const APP_NAME = "__app__";
	const APP_PLUGIN = "CFApp";
	const APP = 0;
	const PLUGIN = 1;
	const CORE = 2;

	private static $plugins = array();
	private static $plugins_list = array();
	private static $core_list = array();
	private static $app_list = array();
	private static $autoload_registered = false;

	private $name;
	private $dir;


	public function __construct($dir, $name) {
		$this->name = $name;
		$this->dir = $dir;
		$this->init();
	}


	protected function init() {

	}


	public static function add($name, $position = self::PLUGIN, $dir = null, $class_name = null) {
		if (array_key_exists($name, self::$plugins))
			return;

		if ($class_name == null)
			$class_name = $name;
		$class_fullname = __NAMESPACE__ . "\\" . $class_name . "Plugin";

		if (class_exists($class_fullname)) {
			$reflector = new \ReflectionClass($class_fullname);
			$dir = dirname($reflector->getFileName());
			unset($reflector);
		}

		if ($dir === null) {
			$dir = PLUGINS_DIR . DIRECTORY_SEPARATOR . $name;
			if (! is_dir($dir))
				$dir = CF_PLUGINS_DIR . DIRECTORY_SEPARATOR . $name;
		}

		if (! is_dir($dir))
			throw new Exception("Plugin $name not found");

		$class_file = $dir . DIRECTORY_SEPARATOR . $class_name . '.plugin.php';

		if (file_exists($class_file)) {
			require_once($class_file);
			$plugin = new $class_fullname($dir, $name);
		} else {
			$plugin = new self($dir, $name);
		}
		self::$plugins[$name] = $plugin;
		switch($position) {
			case self::APP:
				self::$app_list[] = $name;
				break;
			case self::PLUGIN:
				self::$plugins_list[] = $name;
				break;
			case self::CORE:
				array_unshift(self::$core_list, $name);
				break;
		}
	}


	public static function addApp() {
		self::add(self::APP_NAME, self::APP, ROOT_DIR, self::APP_PLUGIN);
	}


	public static function addAll($dir, $position = self::PLUGIN) {
		if (is_dir($dir)) {
			if ($dh = opendir($dir)) {
				while (($file = readdir($dh)) !== false) {
					if (is_dir($dir . DIRECTORY_SEPARATOR . $file) && $file[0] != ".") {
						self::add($file, $position, $dir . DIRECTORY_SEPARATOR . $file);
					}
				}
				closedir($dh);
			}
		}
	}


	public static function get_plugins($reversed=false) {
		if ($reversed)
			return array_merge(array_reverse(self::$app_list), array_reverse(self::$plugins_list), array_reverse(self::$core_list));
		else
			return array_merge(self::$app_list, self::$plugins_list, self::$core_list);
	}


	public static function get($name) {
		return self::$plugins[$name];
	}


	public static function find($filename, $reversed=true) {
		if (class_exists("MemCache", true)) {
			$memcached = new MemCache();
			if ($memcached->offsetExists("plugin." . $filename))
				return $memcached["plugin." . $filename];
		} else {
			$memcached = array();
		}
		foreach(self::get_plugins($reversed) as $plugin) {
			$resource = self::get($plugin)->getDir() . DIRECTORY_SEPARATOR . $filename;
			if (file_exists($resource)) {
				$memcached["plugin." . $filename] = $resource;
				return $resource;
			}
		}
		$memcached["plugin." . $filename] = null;
		return null;
	}


	public static function findFrom($plugins, $filename) {
		$files = array();
		foreach($plugins as $plugin) {
			$resource = self::get($plugin)->getDir() . DIRECTORY_SEPARATOR . $filename;
			if (file_exists($resource)) {
				$files[] = $resource;
			}
		}
		return $files;
	}


	public static function findAll($filename) {
		return self::findFrom(self::get_plugins(), $filename);
	}


	/**
	 * @param string $method_name
	 * @param mixed $method_arguments
	 *
	 * @return mixed
	 */
	public static function dispatch() {
		$arguments = func_get_args();
		$method_name = array_shift($arguments);

		foreach(self::get_plugins() as $plugin) {
			$class = self::get($plugin);
			if (method_exists($class, $method_name)) {
				Logger::debug("Dispatch " . $plugin . "::" . $method_name);
				return call_user_func_array(array($class, $method_name), $arguments);
			}
		}

		return null;
	}


	/**
	 * @param array $plugins
	 * @param string $method_name
	 * @param mixed $method_arguments
	 *
	 * @return array
	 */
	public static function dispatchTo() {
		$results = array();
		$arguments = func_get_args();
		$plugins = array_shift($arguments);
		$method_name = array_shift($arguments);

		foreach($plugins as $plugin) {
			$class = self::get($plugin);
			if (method_exists($class, $method_name)) {
				Logger::debug("Dispatch " . $plugin . "::" . $method_name);
				$results[] = call_user_func_array(array($class, $method_name), $arguments);
			}
		}

		return $results;
	}


	/**
	 * @param string $method_name
	 * @param mixed $method_arguments
	 *
	 * @return array
	 */
	public static function dispatchAll() {
		$arguments = func_get_args();
		array_unshift($arguments, self::get_plugins());
		return call_user_func_array(array(__CLASS__, "dispatchTo"), $arguments);
	}


	/**
	 * @param string $method_name
	 * @param mixed $method_arguments
	 *
	 * @return array
	 */
	public static function dispatchAllReversed() {
		$arguments = func_get_args();
		array_unshift($arguments, array_reverse(self::get_plugins()));
		return call_user_func_array(array(__CLASS__, "dispatchTo"), $arguments);
	}


	public function getDir() {
		return $this->dir;
	}


	protected function removeNamespace($class_name) {
		$pos = strrpos($class_name, "\\");
		if ($pos === false)
			return $class_name;
		return substr($class_name, $pos + 1);
	}


	protected function autoload($class_name) {
		$class_name = $this->removeNamespace($class_name);
		$class_file = $this->dir . DIRECTORY_SEPARATOR . self::CLASS_DIR . DIRECTORY_SEPARATOR . $class_name . '.class.php';
		if (is_readable($class_file)) {
			require_once($class_file);
			return true;
		}

		return false;
	}


	public static function spl_autoload($class_name) {
		foreach(self::get_plugins() as $plugin) {
			if (self::get($plugin)->autoload($class_name))
				return;
		}
	}


	public static function registerAutoload() {
		if (!self::$autoload_registered) {
			spl_autoload_register(__NAMESPACE__ . "\\Plugins::spl_autoload");
			self::$autoload_registered = true;
		}
	}

}


class Options {
	private static $options = array(); // name => [description, modified]
	private static $import = false;

	public static function set($key, $value, $description = null) {
		if (!defined($key)) {
			self::$options[$key] = array($description, self::$import);
			define($key, $value);
		} elseif (array_key_exists($key, self::$options) === false) {
			self::$options[$key] = array($description, self::$import);
		} else {
			if ($description != null)
				self::$options[$key][0] = $description;
			if (self::$import)
				self::$options[$key][1] = true;
		}
	}


	public static function get($key) {
		return constant($key);
	}


	public static function description($key) {
		return isset(self::$options[$key]) ? self::$options[$key][0] : null;
	}


	public static function updated($key) {
		return isset(self::$options[$key]) ? self::$options[$key][1] : false;
	}


	public static function getAll($filter = false) {
		$ret = array();
		foreach(self::$options as $key => $val) {
			if (!$filter || $val[1])
				$ret[$key] = constant($key);
		}
		return $ret;
	}


	public static function import($filename) {
		self::$import = true;
		require_once($filename);
		self::$import = false;
	}


	public static function updateConf($values, $local = true) {
		if ($local)
			$conf = fopen(INIT_CONFIG_DIR . DIRECTORY_SEPARATOR . "config.local.php", "w");
		else
			$conf = fopen(INIT_CONFIG_DIR . DIRECTORY_SEPARATOR . "config.php", "w");
		fwrite($conf, "<?php namespace " . __NAMESPACE__ . ";\n\n");
		$opts = array_merge(self::getAll(true), $values);
		ksort($opts);
		foreach($opts as $key => $val) {
			if (is_bool($val))
				$val = $val ? "true" : "false";
			elseif (is_int($val))
					$val = intval($val);
			elseif (is_string($val))
				$val = '"' . addslashes($val) . '"';
			if ($val !== null) {
				$desc = self::description($key);
				fwrite($conf, "Options::set(\"$key\", $val");
				if ($desc !== NULL)
					if ($local)
						fwrite($conf, "); // $desc");
					else
						fwrite($conf, ', "' . addslashes($desc) . '"');
				if (!$local || $desc === NULL)
					fwrite($conf, ");");
				fwrite($conf, "\n");
			}
		}
		fclose($conf);
	}

}


ob_start();

function configure($key, $value) {
	Options::set($key, $value);
}

define("URL_SEPARATOR", "/");
define("START_TIME", microtime(true));
Options::set("CF_VERSION", "2.2");
if (defined("ROOT_DIR")) {
	Options::set("INIT_CONFIG_DIR", ROOT_DIR . DIRECTORY_SEPARATOR . "config");
} else {
	Options::set("INIT_CONFIG_DIR", dirname(dirname(__file__)) . DIRECTORY_SEPARATOR . "config");
}

if (file_exists(INIT_CONFIG_DIR . DIRECTORY_SEPARATOR . "config.local.php")) {
	Options::import(INIT_CONFIG_DIR . DIRECTORY_SEPARATOR . "config.local.php");
}

if (file_exists(INIT_CONFIG_DIR . DIRECTORY_SEPARATOR . "config.php")) {
	Options::import(INIT_CONFIG_DIR . DIRECTORY_SEPARATOR . "config.php");
}

Options::set("MINIMUM_PHP_VERSION", "5.4.0");
if (version_compare(MINIMUM_PHP_VERSION, PHP_VERSION, '>'))
	die("PHP " . MINIMUM_PHP_VERSION . " required." . PHP_EOL);

if (!defined("ROOT_DIR"))
	die("ROOT_DIR not defined." . PHP_EOL);

Options::set("CF_DIR", dirname(__file__), "Path to the framework");
Options::set("CF_PLUGINS_DIR", CF_DIR, "Path to the core plugins");
Options::set("PLUGINS_DIR", ROOT_DIR . DIRECTORY_SEPARATOR . "plugins", "Path to the application plugins");
Options::set("ROOT_DIR", dirname(CF_DIR), "Application home folder");
Options::set("CONFIG_DIR", ROOT_DIR . DIRECTORY_SEPARATOR . "config", "Application configuration folder");
Options::set("CORE_PLUGIN", "Core", "Main plugin to load");
Options::set("FORCE_HTTPS", false, "Use https by default");
Options::set("USE_STS", false, "Use Strict Transport Security header");
Options::set("DEFAULT_TIMEZONE", "UTC", "Server timezone");
Options::set("DEBUG", false, "For development only");
Options::set("IS_CLI", defined("STDIN") && substr(php_sapi_name(), 0, 3) == "cli");
Options::set("IS_PHAR", substr(__FILE__, 0, 7) == "phar://");

if (!IS_CLI && FORCE_HTTPS && $_SERVER["HTTPS"] != "on") {
	if (USE_STS) {
		header('Strict-Transport-Security: max-age=500');
	} else {
		header('Status-Code: 301');
		header("Location: https://" . $_SERVER["HTTP_HOST"] . $_SERVER["REQUEST_URI"]);
		Output::finish(301);
	}
}

date_default_timezone_set(DEFAULT_TIMEZONE);

if (function_exists('mb_internal_encoding'))
	mb_internal_encoding('UTF-8');

Plugins::registerAutoload();
Plugins::add(CORE_PLUGIN, Plugins::CORE);
Plugins::addApp();
