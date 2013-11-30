<?php

class Logger {

	const DEBUG = 1;
	const INFO = 2;
	const WARNING = 3;
	const ERROR = 4;
	const CRITICAL = 5;

	private static $instance = NULL;
	private $level;


	public function __construct() {
		$this->level = self::ERROR;
	}


	public static function getInstance() {
		if (is_null(self::$instance)) {
			self::$instance = new self();
		}

		return self::$instance;
	}


	public function set_level($level) {
		$this->level = $level;
	}


	public function get_level($level) {
		return $this->level;
	}


	public function log($data, $level) {
		if ($level >= $this->level) {
			error_log($data);
		}
	}


	public static function debug($data) {
		$logger = Logger::getInstance();
		$logger->log($data, self::DEBUG);
	}


	public static function info($data) {
		$logger = Logger::getInstance();
		$logger->log($data, self::INFO);
	}


	public static function warning($data) {
		$logger = Logger::getInstance();
		$logger->log($data, self::WARNING);
	}


	public static function error($data) {
		$logger = Logger::getInstance();
		$logger->log($data, self::ERROR);
	}


	public static function critical($data) {
		$logger = Logger::getInstance();
		$logger->log($data, self::CRITICAL);
	}

}
