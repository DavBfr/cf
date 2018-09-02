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

class Session {
	const rights_key = "RIGHTS";
	const xsrf_token = "XSRF_TOKEN";

	private static $instance = null;


	/**
	 * Session constructor.
	 * @throws \Exception
	 */
	protected function __construct() {
		if (session_id() != '')
			ErrorHandler::error(500, "Session already started");

		session_name(SESSION_NAME);
		session_set_cookie_params(0, SESSION_PATH . '; samesite=' . SESSION_SAME_SITE, SESSION_DOMAIN, FORCE_HTTPS, true);
		session_start();

		if (isset($_SESSION['LAST_ACTIVITY']) && (time() - $_SESSION['LAST_ACTIVITY'] > SESSION_TIMEOUT)) {
			session_unset();
			session_destroy();
		}
		$_SESSION['LAST_ACTIVITY'] = time();

		if (!isset($_SESSION['CREATED'])) {
			$_SESSION['CREATED'] = time();
		} elseif (time() - $_SESSION['CREATED'] > SESSION_REGENERATE) {
			session_regenerate_id(true);
			$_SESSION['CREATED'] = time();
		}
	}


	/**
	 * @return Session
	 * @throws \Exception
	 */
	public static function getInstance() {
		if (is_null(self::$instance)) {
			self::$instance = new self();
		}

		return self::$instance;
	}


	/**
	 *
	 * @throws \Exception
	 */
	public static function delete() {
		self::getInstance();

		if (session_status() === PHP_SESSION_ACTIVE) {
			session_unset();
			session_destroy();
			session_write_close();
		}
		self::$instance = null;

		if (ini_get("session.use_cookies")) {
			$params = session_get_cookie_params();
			setcookie(session_name(), '', time() - 42000,
				$params["path"], $params["domain"],
				$params["secure"], $params["httponly"]
			);
			setcookie(XSRF_TOKEN, '', time() - 42000,
				$params["path"], $params["domain"],
				$params["secure"], $params["httponly"]
			);
		}

		unset($_COOKIE[SESSION_NAME]);
	}


	/**
	 * @return bool
	 */
	public static function isInitialized() {
		return !is_null(self::$instance);
	}


	/**
	 * @return bool
	 */
	public static function hasSession() {
		return array_key_exists(SESSION_NAME, $_COOKIE);
	}


	/**
	 * @return mixed
	 */
	public static function nextCheck() {
		return SESSION_TIMEOUT;
	}


	/**
	 * @param $value
	 * @throws \Exception
	 */
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


	/**
	 * @param $value
	 * @return bool
	 * @throws \Exception
	 */
	public static function hasRight($value) {
		if (self::Has(self::rights_key)) {
			$rights = self::Get(self::rights_key);
		} else {
			$rights = array();
		}
		return in_array($value, $rights);
	}


	/**
	 *
	 * @throws \Exception
	 */
	public static function ensureRight() {
		$pass = false;
		foreach (func_get_args() as $right) {
			if (self::hasRight($right)) {
				$pass = true;
			}
		}
		if (!$pass) {
			ErrorHandler::error(401);
		}
	}


	/**
	 * @param string $key
	 * @param mixed $value
	 * @throws \Exception
	 */
	public static function Set($key, $value) {
		self::getInstance();
		$_SESSION[$key] = $value;
	}


	/**
	 * @param string $key
	 * @return mixed
	 * @throws \Exception
	 */
	public static function Get($key) {
		self::getInstance();
		return $_SESSION[$key];
	}


	/**
	 * @param string $key
	 * @return bool
	 * @throws \Exception
	 */
	public static function Has($key) {
		self::getInstance();
		return array_key_exists($key, $_SESSION);
	}


	/**
	 * @return bool
	 * @throws \Exception
	 */
	public static function isLogged() {
		return self::hasSession() && self::hasRight("logged");
	}


	/**
	 * @return bool
	 * @throws \Exception
	 */
	public static function isLoggedApi() {
		if (self::hasSession() && self::hasRight("logged_api"))
			return true;

		$header = 'HTTP_' . strtoupper(str_replace('-', '_', API_TOKEN_HEADER));
		if (array_key_exists($header, $_SERVER)) {
			$token = $_SERVER[$header];
			return Plugins::dispatch("token_login", $token) === true;
		}

		return false;
	}


	/**
	 *
	 * @throws \Exception
	 */
	public static function ensureLoggedin() {
		if (!self::isLogged() && !self::isLoggedApi()) {
			ErrorHandler::error(401);
		}
	}


	/**
	 *
	 * @throws \Exception
	 */
	public static function ensureLoggedinApi() {
		if (!self::isLoggedApi()) {
			ErrorHandler::error(401);
		}
	}


	/**
	 *
	 * @throws \Exception
	 */
	public static function ensureLoggedinUser() {
		if (!self::isLogged()) {
			ErrorHandler::error(401);
		}
	}


	/**
	 *
	 * @throws \Exception
	 */
	public static function setXsrfToken() {
		$pwd = new Password();
		$token = substr($pwd->hash($pwd->getRandomBytes(32)), 7);
		Session::Set(self::xsrf_token, $token);
		$params = session_get_cookie_params();
		setcookie(XSRF_TOKEN, $token, $params["lifetime"], $params["path"], $params["domain"],
			$params["secure"], false);
	}


	/**
	 * @return bool
	 * @throws \Exception
	 */
	public static function checkXsrfToken() {
		if (DEBUG)
			return true;

		$header = 'HTTP_' . strtoupper(str_replace('-', '_', XSRF_HEADER));
		if (!array_key_exists($header, $_SERVER))
			return false;

		return Session::Get(self::xsrf_token) == $_SERVER[$header];
	}


	/**
	 *
	 * @throws \Exception
	 */
	public static function ensureXsrfToken() {
		if (!self::checkXsrfToken()) {
			ErrorHandler::error(401);
		}
	}

}
