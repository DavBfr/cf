<?php
configure("ERROR_TEMPLATE", TEMPLATES_DIR . DIRECTORY_SEPARATOR . "error.php");


class ErrorHandler {
	private static $instance = NULL;
	private $inerror = False;
	private $backtrace = Array();


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


	protected function addBacktrace($filename, $lineno, $class = NULL, $function = NULL, $args = NULL) {
		$this->backtrace[] = array($filename, $lineno, $class, $function, $args);
	}


	private function formatErrorBody($code, $message, $body, $backtrace, $baseline) {
		if (file_exists(ERROR_TEMPLATE)) {
			$tpt = new Template(array(
				"code" => $code,
				"message" => $message,
				"body" => $body,
				"backtrace" => $backtrace,
				"baseline" => $baseline
			));
			header("Content-type: text/html");
			die($tpt->parse(ERROR_TEMPLATE));
		}
		header("Content-type: text/plain");
		$body = "$code $message : $body";
		$body .= "\n\nBacktrace:\n";
		foreach($backtrace as $n=>$bt) {
			$body .=
			"#$n"
			." ${bt[0]} (${bt[1]}):\n"
			.(isset($bt[2]) ? $bt[2] . '->' : '')
			.(isset($bt[3]) ? $bt[3] . '(' . implode(', ', $bt[4]) . ')' : '')
			."\n\n";
		}
		$body .= $baseline;
		die($body);
	}


	public function send_error($code, $message = NULL, $body = NULL, $backtrace=1) {
		if ($this->inerror) {
			echo("Already processing error (send_error) $code $message $body");
			die();
		}

		$this->inerror = True;
		$protocol = (isset($_SERVER['SERVER_PROTOCOL']) ? $_SERVER['SERVER_PROTOCOL'] : 'HTTP/1.0');

		if ($message == NULL) {
			switch ($code) {
				case 500: $message = "Internal server error"; break;
				case 204: $message = "No Content"; break;
				case 404: $message = "Path not found"; break;
				case 401: $message = "Unauthorized"; break;
				case 417: $message = "Expectation failed"; break;
				default: $message = "Error #${code}"; break;
			}
		}

		header("$protocol $code $message");

		if ($body === NULL)
			$body = $message;

		if ($code >= 500) {
			Logger::Error("$code $message $body");
		} else {
			Logger::Info("$code $message $body");
		}

		DEBUG || die();

		if ($backtrace !== False) {
			$this->debugBacktrace($backtrace);
		}

		$baseline = "CF " . CF_VERSION . " ⠶ PHP " . PHP_VERSION . " ⠶ " . $_SERVER["SERVER_SOFTWARE"];

		$this->formatErrorBody($code, $message, $body, $this->backtrace, $baseline);
	}


	public function error_handler($errno, $errstr, $errfile, $errline) {
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
			$this->addBacktrace($v['file'], $v['line'], isset($v['class']) ? $v['class'] : NULL, $v['function'], $v['args']);
		}
	}


	function exception_handler(Exception $e) {
		$this->addBacktrace($e->getFile(), $e->getLine());
		send_error(500, NULL, get_class( $e ) . ": " . $e->getMessage(), False);
	}


	function check_for_fatal() {
		$e = error_get_last();
		if ($e["type"] == 4 || $e["type"] == 1) {
			$this->addBacktrace($e["file"], $e["line"]);
			send_error(500, NULL, $e["type"] . " " . $e["message"], False);

		}
	}

}
