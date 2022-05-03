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
	const xsrf_token = "Options::get('XSRF_TOKEN')";

	private static $instance = null;


	/**
	 * Session constructor.
	 * @throws \Exception
	 */
	protected function __construct() {
		if (session_id() != '')
			ErrorHandler::error(500, "Session already started");

		session_name(Options::get('SESSION_NAME'));
		if (version_compare(PHP_VERSION, '7.3.0') < 0) {
			session_set_cookie_params(0, Options::get('SESSION_PATH') . '; samesite=' . Options::get('SESSION_SAME_SITE'), Options::get('SESSION_DOMAIN'), Options::get('FORCE_HTTPS'), true);
		} else {
			session_set_cookie_params([
				'lifetime' => 0,
				'path' => Options::get('SESSION_PATH'),
				'domain' => Options::get('SESSION_DOMAIN'),
				'secure' => Options::get('FORCE_HTTPS'),
				'httponly' => true,
				'samesite' => Options::get('SESSION_SAME_SITE')]);
		}
		session_start();

		if (isset($_SESSION['LAST_ACTIVITY']) && (time() - $_SESSION['LAST_ACTIVITY'] > Options::get('SESSION_TIMEOUT'))) {
			session_unset();
			session_destroy();
		}
		$_SESSION['LAST_ACTIVITY'] = time();

		if (!isset($_SESSION['CREATED'])) {
			$_SESSION['CREATED'] = time();
		} elseif (time() - $_SESSION['CREATED'] > Options::get('SESSION_REGENERATE')) {
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
			if (ini_get("session.use_cookies")) {
				header_remove('Set-Cookie');
			}
		}
		self::$instance = null;

		if (isset($_COOKIE[Options::get('SESSION_NAME')])) {
			$params = session_get_cookie_params();
			setcookie(session_name(), '', time() - 42000,
				$params["path"], $params["domain"],
				$params["secure"], $params["httponly"]
			);
			unset($_COOKIE[Options::get('SESSION_NAME')]);
		}

		if (isset($_COOKIE[Options::get('XSRF_TOKEN')])) {
			setcookie(Options::get('XSRF_TOKEN'), '', time() - 42000,
				$params["path"], $params["domain"],
				$params["secure"], $params["httponly"]
			);
			unset($_COOKIE[Options::get('XSRF_TOKEN')]);
		}
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
		return array_key_exists(Options::get('SESSION_NAME'), $_COOKIE);
	}


	/**
	 * @return mixed
	 */
	public static function nextCheck() {
		return Options::get('SESSION_TIMEOUT');
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
		return array_key_exists($key, $_SESSION) ? $_SESSION[$key] : null;
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

		if (HttpHeaders::contains(Options::get('API_TOKEN_HEADER'))) {
			$token = HttpHeaders::get(Options::get('API_TOKEN_HEADER'));
			$login = Plugins::dispatch("token_login", $token);
			Logger::error("token_login result: " . var_export($login, true));
			return $login === true;
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
		setcookie(Options::get('XSRF_TOKEN'), $token, $params["lifetime"], $params["path"], $params["domain"],
			$params["secure"], false);
	}


	/**
	 * @return bool
	 * @throws \Exception
	 */
	public static function checkXsrfToken() {
		if (Options::get('DEBUG'))
			return true;

		if (!HttpHeaders::contains(Options::get('XSRF_HEADER')))
			return false;

		return Session::Get(self::xsrf_token) == HttpHeaders::get(Options::get('XSRF_HEADER'));
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
