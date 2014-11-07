<?php
/**
 * Copyright (C) 2013-2014 David PHAM-VAN
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License
 * as published by the Free Software Foundation; version 2
 * of the License.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301, USA.
 **/

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
	private $log;

	private function __construct($level) {
		$this->level = $level;
		$this->stderr = fopen('php://stderr', 'w');
		$this->log = array();
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


	public function log($data, $level) {
		if ($level >= $this->level || DEBUG) {
			$raddr = array_key_exists('REMOTE_ADDR', $_SERVER) ? $_SERVER['REMOTE_ADDR'] : '-';
			$data = "[CF] [" . @date('M j H:i:s') . "] [" . $raddr . "] [" . self::$levels[$level] . "] " . $data;
			fwrite($this->stderr, $data . "\n");
			if (DEBUG) {
				$this->log[] = $data;
			}
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
