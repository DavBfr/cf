<?php

class Plugins {
	const CLASS_DIR = "classes";
	const APP_NAME = "__app__";
	const APP = 0;
	const PLUGIN = 1;
	const CORE = 2;

	private static $instance = NULL;
	private static $plugins = array();
	private static $plugins_list = array();
	private static $core_list = array();
	private static $app_list = array();

	private $name;
	private $dir;


	public function __construct($dir, $name) {
		$this->name = $name;
		$this->dir = $dir;
	}


	public static function add($name, $position = self::PLUGIN, $dir = NULL) {
		if (array_key_exists($name, self::$plugins))
			return;
		
		if ($dir === NULL)
			$dir = CF_PLUGINS_DIR . DIRECTORY_SEPARATOR . $name;
		
		$class_file = $dir . DIRECTORY_SEPARATOR . $name . '.plugin.php';
		if (file_exists($class_file)) {
			require_once($class_file);
			$class_name = $name."Plugin";
			$plugin = new $class_name($dir, $name);
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
		self::add(self::APP_NAME, self::APP, ROOT_DIR);
	}


	public static function get_plugins() {
		return array_merge(self::$app_list, self::$plugins_list, self::$core_list);
	}


	public static function get($name) {
		return self::$plugins[$name];
	}


	public static function find($filename) {
		foreach(self::get_plugins() as $plugin) {
			$resource = self::get($plugin)->getDir() . DIRECTORY_SEPARATOR . $filename;
			if (file_exists($resource)) {
				return $resource;
			}
		}
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


	public function getDir() {
		return $this->dir;
	}


	public function autoload($class_name) {
		$class_file = $this->dir . DIRECTORY_SEPARATOR . self::CLASS_DIR . DIRECTORY_SEPARATOR . $class_name . '.class.php';
		if (file_exists($class_file)) {
			require_once($class_file);
			return True;
		}
		
		return False;
	}

}
