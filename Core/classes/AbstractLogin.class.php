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

abstract class AbstractLogin extends Rest {
	const userid = "userid";
	const logged = "logged";
	const apiid = "apiid";
	const logged_api = "logged_api";


	/**
	 *
	 */
	protected function getRoutes() {
		$this->addRoute("/", "POST", "doLogin");
		$this->addRoute("/api", "GET", "doApiLogin");
		$this->addRoute("/logout", "GET", "logout");
		$this->addRoute("/check", "GET", "check");
		$this->addRoute("/user", "GET", "user");
	}


	/**
	 * @param array $r
	 * @throws \Exception
	 */
	protected function logout($r) {
		Session::delete();
		Output::success(array("message" => Lang::get("core.disconnected")));
	}


	/**
	 * @param array $r
	 * @throws \Exception
	 */
	protected function check($r) {
		$user = Session::isLogged();
		$api = Session::isLoggedApi();
		if (Session::Has(Session::rights_key)) {
			$rights = Session::Get(Session::rights_key);
		} else {
			$rights = array();
		}
		if ($user || $api) {
			if (Session::Has(self::userid))
				$userid = Session::Get(self::userid);
			else
				$userid = false;
			if (Session::Has(self::apiid))
				$apiid = Session::Get(self::apiid);
			else
				$apiid = false;
			Output::success(array("user" => $userid, "api" => $apiid, "rights" => $rights, "next" => Session::nextCheck()));
		}
		Output::error("Not loggied in", 401);
	}


	/**
	 * @param int $userid
	 * @return array
	 */
	protected function getUserData($userid) {
		return array();
	}


	/**
	 * @param array $r
	 * @throws \Exception
	 */
	protected function user($r) {
		$user = Session::isLogged();
		if (Session::Has(Session::rights_key)) {
			$rights = Session::Get(Session::rights_key);
		} else {
			$rights = array();
		}
		if ($user) {
			if (Session::Has(self::userid)) {
				$userid = Session::Get(self::userid);
				$userdata = $this->getUserData($userid);
			} else {
				$userid = false;
				$userdata = array();
			}
			Output::success(array_merge(array("user" => $userid, "rights" => $rights), $userdata));
		}
		Output::success(array("user" => false, "rights" => array()));
	}


	/**
	 * @param array $r
	 * @throws \Exception
	 */
	protected function doLogin($r) {
		$post = $this->jsonpost();
		Input::ensureRequest($post, array("username", "password"));

		if (($user = $this->login($post["username"], $post["password"])) !== false) {
			Session::Set(self::userid, $user);
			Session::addRight(self::logged);
			if (Session::Has(Session::rights_key)) {
				$rights = Session::Get(Session::rights_key);
			} else {
				$rights = array();
			}

			Session::setXsrfToken();
			Output::success(array("user" => $user, "rights" => $rights));
		}
		ErrorHandler::error(401);
	}


	/**
	 * @param array $r
	 * @throws \Exception
	 */
	protected function doApiLogin($r) {
		Input::ensureRequest($_REQUEST, array("token"));

		if (($apiid = $this->apiLogin($_REQUEST["token"])) !== false) {
			Session::Set(self::apiid, $apiid);
			Session::addRight(self::logged_api);
			Output::success();
		}
		ErrorHandler::error(401);
	}


	/**
	 * @param string $username
	 * @param string $password
	 * @return bool
	 */
	abstract protected function login($username, $password);


	/**
	 * @param string $token
	 * @return bool
	 */
	abstract protected function apiLogin($token);

}
