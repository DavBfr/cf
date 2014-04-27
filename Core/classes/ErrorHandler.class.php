<?php

configure("ERROR_TEMPLATE", "error.php");


class ErrorHandler {
	private static $instance = NULL;
	private $inerror = False;
	private $backtrace = Array();
	private $raise_exception = False;

	public static $messagecode = array(
				500 => "Internal server error",
				204 => "No Content",
				404 => "Path not found",
				401 => "Unauthorized",
				417 => "Expectation failed",
	);

	protected function __construct() {
		error_reporting(E_ALL ^ (E_NOTICE|E_WARNING));
		ini_set("display_errors", 0);
		set_error_handler(array($this, "error_handler"));
		set_exception_handler(array($this, "exception_handler"));
		register_shutdown_function(array($this, "check_for_fatal"));
	}


	public static function Init($className) {
		if (is_null(self::$instance)) {
			self::$instance = new $className();
		}
	}


	public static function getInstance() {
		if (is_null(self::$instance)) {
			self::$instance = new self();
		}

		return self::$instance;
	}


	public static function RaiseExceptionOnError() {
		$i = self::getInstance();
		$i->raise_exception = True;
	}


	protected function addBacktrace($filename, $lineno, $class = NULL, $function = NULL, $args = NULL) {
		$this->backtrace[] = array($filename, $lineno, $class, $function, $args);
	}


	protected function formatErrorBody($code, $message, $body, $backtrace = array(), $log = array()) {
		$baseline = CorePlugin::getBaseline();
		if ($message === NULL) {
			if (isset(self::$messagecode[$code]))
				$message = self::$messagecode[$code];
			else
				$message = "Error #${code}";
		}
		
		 if (!DEBUG) {
			$body = "";
			$backtrace = array();
		}

		if (Template::findTemplate(ERROR_TEMPLATE) && !IS_CLI) {
			$tpt = new Template(array(
				"code" => $code,
				"message" => $message,
				"body" => $body,
				"backtrace" => $backtrace,
				"baseline" => $baseline,
				"log" => $log
			));
			header("Content-type: text/html");
			$tpt->output(ERROR_TEMPLATE);
		}
		header("Content-type: text/plain");
		$body = "$message ($code)\n$body";
		if (is_array($backtrace) && count($backtrace)>0) {
			$body .= "\n\nBacktrace:\n";
			foreach($backtrace as $n=>$bt) {
				$body .=
				"#$n"
				." ${bt[0]} (${bt[1]}):\n"
				.(isset($bt[2]) ? $bt[2] . '->' : '')
				.(isset($bt[3]) ? $bt[3] . '(' . implode(', ', $bt[4]) . ')' : '')
				."\n";
			}
		}
		$body .= "\n---\n" . $baseline . "\n";
		die($body);
	}


	public static function error($code, $message = NULL, $body = NULL, $backtrace=2) {
		self::getInstance()->send_error($code, $message, $body, $backtrace);
	}


	public function send_error($code, $message = NULL, $body = NULL, $backtrace=1) {
		if ($this->inerror) {
			die("Already processing error (send_error) $code $message $body");
		}

		$this->inerror = True;
		$protocol = (isset($_SERVER['SERVER_PROTOCOL']) ? $_SERVER['SERVER_PROTOCOL'] : 'HTTP/1.0');

		if ($message == NULL) {
			if (isset(self::$messagecode[$code]))
				$message = self::$messagecode[$code];
			else
				$message = "Error #${code}";
		}

		if ($body === NULL)
			$body = $message;

		if ($code >= 500) {
			Logger::Error("[$code] $message: $body");
		} else {
			Logger::Info("[$code] $message: $body");
		}

		if ($this->raise_exception)
			throw new Exception($body);

		header("$protocol $code $message");
		
		if ($code < 500 && $code != 404)
			die($body);

		IS_CLI && die();

		if ($backtrace !== False) {
			$this->debugBacktrace($backtrace);
		}

		$this->formatErrorBody($code, $message, $body, $this->backtrace, array_slice(Logger::getInstance()->getLog(), 0, -1));
	}


	public function errorHandler($errno, $errstr, $errfile, $errline) {
		if ($this->inerror) {
			echo("Already processing error (error_handler) $errno, $errstr, $errfile, $errline");
			die();
		}

		if (!(error_reporting() & $errno)) {
			return;
		}

		$this->addBacktrace($errfile, $errline);
		$this->send_error(500, NULL, "Error $errno $errstr", False);
	}


	protected function debugBacktrace($ignore = 1) {
		$trace = '';
		foreach (debug_backtrace() as $k => $v) {
			if ($k < $ignore) {
				continue;
			}
			array_walk($v['args'], function (&$item, $key) {
				$item = var_export($item, true);
			});
			$this->addBacktrace(isset($v['file']) ? $v['file'] : '', isset($v['line']) ? $v['line'] : "", isset($v['class']) ? $v['class'] : NULL, $v['function'], $v['args']);
		}
	}


	public function exceptionHandler(Exception $e) {
		$this->addBacktrace($e->getFile(), $e->getLine());
		$this->send_error(500, NULL, get_class( $e ) . ": " . $e->getMessage(), False);
	}


	public function checkForFatal() {
		$e = error_get_last();
		if ($e && error_reporting() & $e["type"]) {
			$this->addBacktrace($e["file"], $e["line"]);
			$this->send_error(500, NULL, $e["type"] . " " . $e["message"], False);

		}
	}

}