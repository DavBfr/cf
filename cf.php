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

class Plugins {
	const CLASS_DIR = "classes";
	const APP_NAME = "__app__";
	const APP_PLUGIN = "CFApp";
	const APP = 0;
	const PLUGIN = 1;
	const CORE = 2;

	private static $instance = NULL;
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


	public static function add($name, $position = self::PLUGIN, $dir = NULL, $class_name = NULL) {
		if (array_key_exists($name, self::$plugins))
			return;

		if ($class_name == NULL)
			$class_name = $name;
		$class_fullname = $class_name . "Plugin";

		if (class_exists($class_fullname)) {
			$reflector = new ReflectionClass($class_fullname);
			$dir = dirname($reflector->getFileName());
			unset($reflector);
		}

		if ($dir === NULL) {
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


	public static function get_plugins() {
		return array_merge(self::$app_list, self::$plugins_list, self::$core_list);
	}


	public static function get($name) {
		return self::$plugins[$name];
	}


	public static function find($filename) {
		if (class_exists("MemCache", true)) {
			$memcached = new MemCache();
			if ($memcached->offsetExists("plugin.".$filename))
				return $memcached["plugin.".$filename];
		} else {
			$memcached = Array();
		}
		foreach(self::get_plugins() as $plugin) {
			$resource = self::get($plugin)->getDir() . DIRECTORY_SEPARATOR . $filename;
			if (file_exists($resource)) {
				$memcached["plugin.".$filename] = $resource;
				return $resource;
			}
		}
		$memcached["plugin.".$filename] = NULL;
		return NULL;
	}


	public static function findAll($filename) {
		$files = array();
		foreach(self::get_plugins() as $plugin) {
			$resource = self::get($plugin)->getDir() . DIRECTORY_SEPARATOR . $filename;
			if (file_exists($resource)) {
				$files[] = $resource;
			}
		}
		return $files;
	}


	public static function dispatch() {
		$arguments = func_get_args();
		$method_name = array_shift($arguments);

		foreach(self::get_plugins() as $plugin) {
			$class = self::get($plugin);
			if (method_exists($class, $method_name)) {
				Logger::debug("Dispatch ".$plugin."::".$method_name);
				return call_user_func_array(array($class, $method_name), $arguments);
			}
		}

		return NULL;
	}


	public static function dispatchAll() {
		$results = array();
		$arguments = func_get_args();
		$method_name = array_shift($arguments);

		foreach(self::get_plugins() as $plugin) {
			$class = self::get($plugin);
			if (method_exists($class, $method_name)) {
				Logger::debug("Dispatch ".$plugin."::".$method_name);
				$results[] = call_user_func_array(array($class, $method_name), $arguments);
			}
		}

		return $results;
	}


	public static function dispatchAllReversed() {
		$results = array();
		$arguments = func_get_args();
		$method_name = array_shift($arguments);

		foreach(array_reverse(self::get_plugins()) as $plugin) {
			$class = self::get($plugin);
			if (method_exists($class, $method_name)) {
				Logger::debug("Dispatch ".$plugin."::".$method_name);
				$results[] = call_user_func_array(array($class, $method_name), $arguments);
			}
		}

		return $results;
	}


	public function getDir() {
		return $this->dir;
	}


	protected function autoload($class_name) {
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
			spl_autoload_register("Plugins::spl_autoload");
			self::$autoload_registered = true;
		}
	}

}

ob_start();

$configured_options = array();

function configure($key, $value) {
	global $configured_options;
	if (!defined($key)) {
		$configured_options[] = $key;
		define($key, $value);
	} elseif (array_search($key, $configured_options) === false) {
		$configured_options[] = $key;
	}
}

define("URL_SEPARATOR", "/");
configure("CF_VERSION", "1.2");
if (defined("ROOT_DIR")) {
	configure("INIT_CONFIG_DIR", ROOT_DIR . DIRECTORY_SEPARATOR . "config");
} else {
	configure("INIT_CONFIG_DIR", dirname(dirname(__file__)) . DIRECTORY_SEPARATOR . "config");
}

if (file_exists(INIT_CONFIG_DIR . DIRECTORY_SEPARATOR . "config.local.php")) {
	require_once(INIT_CONFIG_DIR . DIRECTORY_SEPARATOR . "config.local.php");
}

if (file_exists(INIT_CONFIG_DIR . DIRECTORY_SEPARATOR . "config.php")) {
	require_once(INIT_CONFIG_DIR . DIRECTORY_SEPARATOR . "config.php");
}

configure("CF_DIR", dirname(__file__));
configure("CF_PLUGINS_DIR", CF_DIR);
configure("PLUGINS_DIR", ROOT_DIR . DIRECTORY_SEPARATOR . "plugins");
configure("ROOT_DIR", dirname(CF_DIR));
configure("CONFIG_DIR", ROOT_DIR . DIRECTORY_SEPARATOR . "config");
configure("CORE_PLUGIN", "Core");
configure("FORCE_HTTPS", false);
configure("USE_STS", false);
configure("DEFAULT_TIMEZONE", "Europe/Paris");
configure("DEBUG", false);
configure("IS_CLI", defined("STDIN") && substr(php_sapi_name(), 0, 3) == "cli");

if (!IS_CLI && FORCE_HTTPS && $_SERVER["HTTPS"] != "on") {
	if (USE_STS) {
		header('Strict-Transport-Security: max-age=500');
	} else {
		header('Status-Code: 301');
		header("Location: https://" . $_SERVER["HTTP_HOST"] . $_SERVER["REQUEST_URI"]);
		die();
	}
}

date_default_timezone_set(DEFAULT_TIMEZONE);

if (function_exists('mb_internal_encoding'))
	mb_internal_encoding('UTF-8');

Plugins::registerAutoload();
Plugins::add(CORE_PLUGIN, Plugins::CORE);
Plugins::addApp();
