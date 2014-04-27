<?php

configure("JCONFIG_FILE", CONFIG_DIR . DIRECTORY_SEPARATOR . "config.json");

class Config {
	private static $instance = NULL;
	private $data;


	public function __construct() {
		$this->data = array();
	}


	public static function getInstance() {
		if (is_null(self::$instance)) {
			self::$instance = new self();
			$memcache = new MemCache();
			if ($memcache->offsetExists("JCONFIG_FILE")) {
				self::$instance->data = $memcache["JCONFIG_FILE"];
				Logger::debug("Config loaded from cache");
			}
			elseif (file_exists(JCONFIG_FILE)) {
				self::$instance->load(JCONFIG_FILE);
				$memcache["JCONFIG_FILE"] = self::$instance->data;
			}
		}

		return self::$instance;
	}


	public static function jsonLastErrorMsg() {
		if (!function_exists('json_last_error_msg')) {
			static $errors = array(
					JSON_ERROR_NONE             => null,
					JSON_ERROR_DEPTH            => 'Maximum stack depth exceeded',
					JSON_ERROR_STATE_MISMATCH   => 'Underflow or the modes mismatch',
					JSON_ERROR_CTRL_CHAR        => 'Unexpected control character found',
					JSON_ERROR_SYNTAX           => 'Syntax error, malformed JSON',
					JSON_ERROR_UTF8             => 'Malformed UTF-8 characters, possibly incorrectly encoded'
			);
			$error = json_last_error();
			return array_key_exists($error, $errors) ? $errors[$error] : "Unknown error ({$error})";
		}

		return json_last_error_msg();
	}


	public function load($filename) {
		$this->data = json_decode(file_get_contents($filename), True);
		if (json_last_error() !== JSON_ERROR_NONE) {
			ErrorHandler::error(500, NULL, "Error in ${filename} : " . self::jsonLastErrorMsg()); break;
		}
	}


	public function save($filename) {
		file_put_contents($filename, json_encode($this->data));
	}


	public function get($key, $default=NULL) {
		$value = $this->data;
		foreach(explode(".", $key) as $item) {
			if (is_array($value) && array_key_exists($item, $value)) {
				$value = $value[$item];
			} else {
				return $default;
			}
		}

		return $value;
	}

}
