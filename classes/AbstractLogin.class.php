<?php

abstract class AbstractLogin extends Rest {

	protected function getRoutes() {
		$this->addRoute("/", "POST", "login");
		$this->addRoute("/api", "GET", "api_login");
		$this->addRoute("/logout", "GET", "logout");
	}


	protected function logout($r) {
		$_SESSION["is_logged"] = false;
		output_json(True);
	}


	protected function login($r) {
		$post = $this->jsonpost();
		ensure_request($post, array("username", "password"));

		if (DEBUG) {
			$_SESSION["userid"] = 0;
			$_SESSION["is_logged"] = true;
			output_json(True);
		}

		if (($user = $this->dologin($post["username"], $post["password"])) !== False) {
			$_SESSION["userid"] = $user;
			$_SESSION["is_logged"] = true;
			output_json(True);
		}
		send_error(401);
	}


	protected function api_login($r) {
		ensure_request($r, array("token"));

		if (($apiid = $this->apilogin($r["token"])) !== False) {
			$_SESSION["apiid"] = $apiid;
			$_SESSION["is_logged"] = true;
			output_json(True);
		}
		send_error(401);
	}

	abstract protected function dologin($username, $password);

	abstract protected function apilogin($token);

}
