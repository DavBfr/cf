<?php

class Logger {

	const DEBUG = 1;
	const INFO = 2;
	const WARNING = 3;
	const ERROR = 4;
	const CRITICAL = 5;

	private static $levels = array(
		self::DEBUG=>"DEBUG",
		self::INFO=>"INFO",
		self::WARNING=>"WARNING",
		self::ERROR=>"ERROR",
		self::CRITICAL=>"CRITICAL"
	);

	private static $instance = NULL;
	private $level;
	private $stderr;

	private function __construct($level) {
		$this->level = $level;
		$this->stderr = fopen('php://stderr', 'w');
	}


	public function __destruct() {
		fclose($this->stderr);
	}


	public static function getInstance() {
		if (is_null(self::$instance)) {
			self::$instance = new self(DEBUG ? self::DEBUG : self::ERROR);
		}

		return self::$instance;
	}


	public function setLevel($level) {
		$this->level = $level;
	}


	public function getLevel($level) {
		return $this->level;
	}


	public function log($data, $level) {
		if ($level >= $this->level) {
			$raddr = array_key_exists('REMOTE_ADDR', $_SERVER) ? $_SERVER['REMOTE_ADDR'] : '-';
			$data = "[CF] [" . @date('M j H:i:s') . "] [" . $raddr . "] [" . self::$levels[$level] . "] " . $data . "\n";
			fwrite($this->stderr, $data);
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
