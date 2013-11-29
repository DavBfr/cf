<?php

class Config {
	private static $instance = NULL;
	private $data;
	
	
	public function __construct() {
		$this->data = array();
	}
	
	
	public static function getInstance() {
		if (is_null(self::$instance)) {
			self::$instance = new self();
			if (file_exists(JCONFIG_FILE)) {
				self::$instance->load(JCONFIG_FILE);
			}
		}
		
		return self::$instance;
	}
	
	
	public function load($filename) {
		$this->data = json_decode(file_get_contents($filename), True);
	}
	
	
	public function save($filename) {
		file_put_contents($filename, json_encode($this->data));
	}
	
	
	public function get($key, $default=NULL) {
		$value = $this->data;
		foreach(explode(".", $key) as $item) {
			if (@array_key_exists($item, $value)) {
				$value = $value[$item];
			} else {
				return $default;
			}
		}
		
		return $value;
	}

}
