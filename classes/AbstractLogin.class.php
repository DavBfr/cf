<?php

abstract class AbstractLogin extends Rest {

	public function getRoutes() {
		$this->addRoute("/", "POST", "login");
		$this->addRoute("/logout", "GET", "logout");
	}


	public function logout() {
		$_SESSION["is_logged"] = false;
		output_json(True);
	}


	public function login() {
		$post = $this->jsonpost();
		
		if (DEBUG) {
			$_SESSION["userid"] = 0;
			$_SESSION["is_logged"] = true;
			output_json(True);
		}
		
		if (isset($post["username"]) && isset($post["password"])) {
			if (($user = $this->dologin($post["username"], $post["password"])) !== False) {
				$_SESSION["userid"] = $user;
				$_SESSION["is_logged"] = true;
				output_json(True);
			}
			send_error(401, "Unauthorized");
		}

		send_error(500, "Error");
	}

	abstract protected function dologin($username, $password);

}
