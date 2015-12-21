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
 
 configure("BROWSER_LOG", DEBUG);

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

	private static $clevels = array(
		self::DEBUG=>"info",
		self::INFO=>"info",
		self::WARNING=>"warn",
		self::ERROR=>"error",
		self::CRITICAL=>"error"
	);

	private static $instance = NULL;
	private $level;
	private $stderr;
	private $log;
	private $clog;

	private function __construct($level) {
		$this->level = $level;
		$this->stderr = fopen('php://stderr', 'w');
		$this->log = array();
		$this->clog = array(
			'version' => CF_VERSION,
			'columns' => array('log', 'backtrace', 'type'),
			'rows' => array(),
			'request_uri' => @$_SERVER['REQUEST_URI']);
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
	
	
	public function getLog() {
		return $this->log;
	}


	protected function logToChrome($data, $level) {
		// https://craig.is/writing/chrome-logger
		$backtrace = debug_backtrace(false);
		$level = 2;

		$backtrace_message = 'unknown';
		if (isset($backtrace[$level]['file']) && isset($backtrace[$level]['line'])) {
				$backtrace_message = $backtrace[$level]['file'] . ' ' . $backtrace[$level]['line'];
		}

		$row = array(
			array($data),
			$backtrace_message,
			self::$clevels[$level]
		);
		$this->clog['rows'][] = $row;

		header("X-ChromeLogger-Data: ".base64_encode(utf8_encode(json_encode($this->clog))));
	}


	public function log($data, $level) {
		if ($level >= $this->level || DEBUG) {
			$raddr = array_key_exists('REMOTE_ADDR', $_SERVER) ? $_SERVER['REMOTE_ADDR'] : '-';
			if (is_array($data)) {
				$output = array();
				foreach($data as $item) {
					if (is_string($item) || is_numeric($item))
						$output[] = (string)$item;
					elseif (is_object($item))
						$output[] = "<" . get_class($item) . (method_exists($item, '__toString') ? ": " . (string)$item : "") . ">";
					else
					$output[] = json_encode($item);
				}
				$data = implode(" ", $output);
			}
			if (BROWSER_LOG && !IS_CLI) {
				$this->logToChrome($data, $level);
			}
			$data = "[CF] [" . @date('M j H:i:s') . "] [" . $raddr . "] [" . self::$levels[$level] . "] " . $data;
			fwrite($this->stderr, $data . "\n");
			if (DEBUG) {
				$this->log[] = $data;
			}
		}
	}


	public static function debug() {
		$logger = Logger::getInstance();
		$logger->log(func_get_args(), self::DEBUG);
	}


	public static function info() {
		$logger = Logger::getInstance();
		$logger->log(func_get_args(), self::INFO);
	}


	public static function warning() {
		$logger = Logger::getInstance();
		$logger->log(func_get_args(), self::WARNING);
	}


	public static function error() {
		$logger = Logger::getInstance();
		$logger->log(func_get_args(), self::ERROR);
	}


	public static function critical() {
		$logger = Logger::getInstance();
		$logger->log(func_get_args(), self::CRITICAL);
	}

}
