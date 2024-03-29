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

class ErrorHandler {
	private static $instance = null;
	private $inerror = false;
	private $backtrace = array();
	private $raise_exception = false;

	public static $messagecode = array(
		500 => "Internal server error",
		204 => "No Content",
		404 => "Path not found",
		400 => "Bad Request",
		401 => "Unauthorized",
		417 => "Expectation failed",
	);


	/**
	 * ErrorHandler constructor.
	 */
	protected function __construct() {
		error_reporting(E_ALL ^ (E_NOTICE | E_USER_NOTICE | (Options::get('DEBUG') ? 0 : (E_WARNING | E_USER_WARNING))));
		ini_set("display_errors", Options::get('DEBUG') ? 1 : 0);
		ini_set("track_errors", 1);
		ini_set("html_errors", 1);
		set_error_handler(array($this, "errorHandler"));
		set_exception_handler(array($this, "exceptionHandler"));
		register_shutdown_function(array($this, "checkForFatal"));
	}


	/**
	 *
	 */
	public static function unregister() {
		restore_error_handler();
		restore_exception_handler();
	}


	/**
	 * @param string $className
	 */
	public static function Init($className) {
		if (is_null(self::$instance)) {
			self::$instance = new $className();
		}
	}


	/**
	 * @return ErrorHandler
	 */
	public static function getInstance() {
		if (is_null(self::$instance)) {
			self::$instance = new self();
		}

		return self::$instance;
	}


	/**
	 *
	 */
	public static function RaiseExceptionOnError() {
		$i = self::getInstance();
		$i->raise_exception = true;
	}


	/**
	 * @param string $filename
	 * @param int $lineno
	 * @param \stdClass $class
	 * @param callable $function
	 * @param array $args
	 */
	protected function addBacktrace($filename, $lineno, $class = null, $function = null, $args = null) {
		$this->backtrace[] = array($filename, $lineno, $class, $function, $args);
	}


	/**
	 * @param array $backtrace
	 * @return string
	 */
	protected function formatTextBacktrace($backtrace) {
		$body = "";
		foreach ($backtrace as $n => $bt) {
			$body .=
				"#$n"
				. " ${bt[0]} (${bt[1]}):\n"
				. (isset($bt[2]) ? $bt[2] . '->' : '')
				. (isset($bt[3]) ? $bt[3] . '(' . implode(', ', $bt[4]) . ')' : '')
				. "\n";
		}
		return $body;
	}


	/**
	 * @param int $code
	 * @param string $message
	 * @param string $body
	 * @param array $backtrace
	 * @param array $log
	 * @throws Exception
	 */
	protected function formatErrorBody($code, $message, $body, $backtrace = array(), $log = array()) {
		$baseline = CorePlugin::getBaseline();

		if ($message === null) {
			if (isset(self::$messagecode[$code]))
				$message = self::$messagecode[$code];
			else
				$message = "Error #${code}";
		}

		if (!Options::get('DEBUG')) {
			$body = "";
			$backtrace = array();
		}

		$http = HttpHeaders::contains('accept') && strpos(HttpHeaders::get('accept'), "text/html") !== false;

		if (!IS_CLI && $http && Template::findTemplate(Options::get('ERROR_TEMPLATE'))) {
			$tpt = new TemplateRes(array(
				"code" => $code,
				"message" => $message,
				"body" => $body,
				"debug" => ob_get_contents(),
				"backtrace" => $backtrace,
				"baseline" => $baseline,
				"log" => $log
			));
			header("Content-type: text/html");
			$tpt->output(Options::get('ERROR_TEMPLATE'));
		}
		header("Content-type: text/plain");
		$body = "$message ($code)\n$body";
		if (is_array($backtrace) && count($backtrace) > 0) {
			$body .= "\n\nBacktrace:\n" . $this->formatTextBacktrace($backtrace);
		}
		$body .= "\n---\n" . $baseline . "\n";
		echo $body;
		Output::finish($code);
	}


	/**
	 * @param int $code
	 * @param string|null $message
	 * @param string|null $body
	 * @param int $backtrace
	 * @param bool|null $finish
	 * @throws Exception
	 */
	public static function error($code, $message = null, $body = null, $backtrace = 2, $finish = null) {
		$i = self::getInstance();
		if ($finish !== null)
			$i->raise_exception = !$finish;
		$i->send_error($code, $message, $body, $backtrace);
	}


	/**
	 * @param int $code
	 * @param string $message
	 * @param string $body
	 * @param int $backtrace
	 * @throws Exception
	 */
	public function send_error($code, $message = null, $body = null, $backtrace = 1) {
		if ($this->inerror) {
			Logger::critical("Already processing error (send_error) $code $message $body");
			return;
		}

		$this->inerror = true;
		$protocol = (isset($_SERVER['SERVER_PROTOCOL']) ? $_SERVER['SERVER_PROTOCOL'] : 'HTTP/1.0');

		if ($message == null) {
			if (isset(self::$messagecode[$code]))
				$message = self::$messagecode[$code];
			else
				$message = "Error #${code}";
		}

		if ($body === null)
			$body = $message;

		if ($code >= 500) {
			Logger::critical("[$code] $message: $body");
		} else if ($code >= 400) {
			Logger::error("[$code] $message: $body");
		} else if ($code >= 300) {
			Logger::warning("[$code] $message: $body");
		} else {
			Logger::info("[$code] $message: $body");
		}

		if ($this->raise_exception) {
			$this->inerror = false;
			throw new Exception($body);
		}

		header("$protocol $code $message");

		if ($code < 500 && $code != 404) {
			echo $body;
			if (Options::get('DEBUG')) {
				echo "\n";
				if ($backtrace !== false) {
					$this->debugBacktrace($backtrace);
					echo "Backtrace:\n" . $this->formatTextBacktrace($this->backtrace);
				}
			}
			Output::finish($code);
		}

		IS_CLI && Output::finish($code);

		if ($backtrace !== false) {
			$this->debugBacktrace($backtrace);
		}

		$this->formatErrorBody($code, $message, $body, $this->backtrace, array_slice(Logger::getInstance()->getLog(), 0, -1));
	}


	/**
	 * @param int $errno
	 * @param string $errstr
	 * @param string $errfile
	 * @param string $errline
	 * @throws Exception
	 */
	public function errorHandler($errno, $errstr, $errfile, $errline) {
		if ($this->inerror) {
			Logger::critical("Already processing error (error_handler) $errno, $errstr, $errfile, $errline");
			return;
		}

		if (!(error_reporting() & $errno)) {
			switch ($errno) {
				case E_NOTICE:
					Logger::info("$errstr in $errfile on line $errline");
					return;
				case E_WARNING:
					Logger::warning("$errstr in $errfile on line $errline");
					return;
				default:
					Logger::error("Error $errno: $errstr in $errfile on line $errline");
					return;
			}
		}

		$this->addBacktrace($errfile, $errline);
		$this->send_error(500, null, "Error $errno $errstr", false);
	}


	/**
	 * @param int $ignore
	 */
	protected function debugBacktrace($ignore = 1) {
		foreach (debug_backtrace() as $k => $v) {
			if ($k < $ignore) {
				continue;
			}
			array_walk($v['args'], function (&$item, $key) {
				$item = var_export($item, true);
			});
			$this->addBacktrace(isset($v['file']) ? $v['file'] : '', isset($v['line']) ? $v['line'] : "", isset($v['class']) ? $v['class'] : null, $v['function'], $v['args']);
		}
	}


	/**
	 * @param Exception $e
	 * @throws Exception
	 */
	public function exceptionHandler($e) {
		$this->addBacktrace($e->getFile(), $e->getLine());
		$this->send_error(500, null, get_class($e) . ": " . $e->getMessage(), false);
	}


	/**
	 * @throws Exception
	 */
	public function checkForFatal() {
		$e = error_get_last();
		if ($e && error_reporting() & $e["type"]) {
			$this->addBacktrace($e["file"], $e["line"]);
			$this->send_error(500, null, $e["type"] . " " . $e["message"], false);
		}
	}

}
