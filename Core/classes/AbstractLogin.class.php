<?php

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
			Output::success(array("user"=>$user, "api"=>$api));
		}
		Output::error("Not loggied in");
	}


	protected function doLogin($r) {
		$post = $this->jsonpost();
		Input::ensureRequest($post, array("username", "password"));

		if (($user = $this->login($post["username"], $post["password"])) !== False) {
			Session::Set("userid", $user);
			Session::addRight("logged");
			Output::success();
		}
		ErrorHandler::error(401);
	}


	protected function doApiLogin($r) {
		Input::ensureRequest($_REQUEST, array("token"));

		if (($apiid = $this->apiLogin($_REQUEST["token"])) !== False) {
			Session::Set("apiid", $apiid);
			Session::addRight("logged_api");
			Output::success();
		}
		ErrorHandler::error(401);
	}

	abstract protected function login($username, $password);

	abstract protected function apiLogin($token);

}
