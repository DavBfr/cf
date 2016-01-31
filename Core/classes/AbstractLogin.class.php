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

	protected function getRoutes() {
		$this->addRoute("/", "POST", "doLogin");
		$this->addRoute("/api", "GET", "doApiLogin");
		$this->addRoute("/logout", "GET", "logout");
		$this->addRoute("/check", "GET", "check");
	}


	protected function logout($r) {
		Session::delete();
		Output::success();
	}


	protected function check($r) {
		$user = Session::isLogged();
		$api = Session::isLoggedApi();
		if ($user || $api) {
			Output::success(array("user"=>$user, "api"=>$api, "next"=>Session::nextCheck()));
		}
		Output::error("Not loggied in");
	}


	protected function doLogin($r) {
		$post = $this->jsonpost();
		Input::ensureRequest($post, array("username", "password"));

		if (($user = $this->login($post["username"], $post["password"])) !== false) {
			Session::Set("userid", $user);
			Session::addRight("logged");
			Output::success();
		}
		ErrorHandler::error(401);
	}


	protected function doApiLogin($r) {
		Input::ensureRequest($_REQUEST, array("token"));

		if (($apiid = $this->apiLogin($_REQUEST["token"])) !== false) {
			Session::Set("apiid", $apiid);
			Session::addRight("logged_api");
			Output::success();
		}
		ErrorHandler::error(401);
	}

	abstract protected function login($username, $password);

	abstract protected function apiLogin($token);

}
