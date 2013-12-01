<?php

abstract class AbstractLogin extends Rest {

	protected function getRoutes() {
		$this->addRoute("/", "POST", "login");
		$this->addRoute("/api", "GET", "api_login");
		$this->addRoute("/logout", "GET", "logout");
		$this->addRoute("/check", "GET", "check");
	}


	protected function logout($r) {
		$_SESSION["is_logged"] = false;
		$_SESSION["is_logged_api"] = false;
		output_success();
	}


	protected function check($r) {
		$user = is_logged();
		$api = is_logged_api();
		if ($user || $api) {
			output_success(array("user"=>$user, "api"=>$api));
		}
		output_error("Not loggied in");
	}	


	protected function login($r) {
		$post = $this->jsonpost();
		ensure_request($post, array("username", "password"));

		if (($user = $this->dologin($post["username"], $post["password"])) !== False) {
			$_SESSION["userid"] = $user;
			$_SESSION["is_logged"] = true;
			output_success();
		}
		send_error(401);
	}


	protected function api_login($r) {
		ensure_request($r, array("token"));

		if (($apiid = $this->apilogin($r["token"])) !== False) {
			$_SESSION["apiid"] = $apiid;
			$_SESSION["is_logged_api"] = true;
			output_success();
		}
		send_error(401);
	}

	abstract protected function dologin($username, $password);

	abstract protected function apilogin($token);

}
