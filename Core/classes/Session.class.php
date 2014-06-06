<?php

class Session {
	const rights_key = "RIGHTS";
	
	private static $instance = NULL;


	protected function __construct() {
		session_name(SESSION_NAME);
		session_start();
	}


	public static function getInstance() {
		if (is_null(self::$instance)) {
			self::$instance = new self();
		}

		return self::$instance;
	}


	public static function delete() {
		self::getInstance();
		session_destroy();
		session_write_close();
		self::$instance = NULL;

		if (ini_get("session.use_cookies")) {
			$params = session_get_cookie_params();
			setcookie(session_name(), '', time() - 42000,
				$params["path"], $params["domain"],
				$params["secure"], $params["httponly"]
			);
		}

		unset($_COOKIE[SESSION_NAME]);
	}


	public static function isInitialized() {
		return ! is_null(self::$instance);
	}


	public static function hasSession() {
		return array_key_exists(SESSION_NAME, $_COOKIE);
	}


	public static function addRight($value) {
		if (self::Has(self::rights_key)) {
			$rights = self::Get(self::rights_key);
		} else {
			$rights = array();
		}
		if (!in_array($value, $rights)) {
			$rights[] = $value;
		}
		self::Set(self::rights_key, $rights);
	}


	public static function hasRight($value) {
		if (self::Has(self::rights_key)) {
			$rights = self::Get(self::rights_key);
		} else {
			$rights = array();
		}
		return in_array($value, $rights);
	}


	public static function ensureRight($value) {
		if (!self::hasRight($value)) {
			ErrorHandler::error(401);
		}
	}


	public static function Set($key, $value) {
		self::getInstance();
		$_SESSION[$key] = $value;
	}


	public static function Get($key) {
		self::getInstance();
		return $_SESSION[$key];
	}


	public static function Has($key) {
		self::getInstance();
		return array_key_exists($key, $_SESSION);
	}


	public static function isLogged() {
		return self::hasSession() && self::hasRight("logged");
	}


	public static function isLoggedApi() {
		return self::hasSession() && self::hasRight("logged_api");
	}


	public static function ensureLoggedin() {
		if (!self::isLogged() && !self::isLoggedApi()) {
			ErrorHandler::error(401);
		}
	}


	public static function ensureLoggedinApi() {
		if (!self::isLoggedApi()) {
			ErrorHandler::error(401);
		}
	}


	public static function ensureLoggedinUser() {
		if (!is_logged()) {
			ErrorHandler::error(401);
		}
	}

}
