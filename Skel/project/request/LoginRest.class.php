<?php namespace DavBfr\CF;

class LoginRest extends AbstractLogin {

	protected function login($username, $password) {
		$users = new UserModel();
		$userid = $users->dologin($username, $password);

		if ($userid === False)
		ErrorHandler::error(401, "Invalid username or password");

		return $userid;
	}

	protected function apiLogin($token) {
		return false;
	}


	protected function check($r) {
		Output::success(array("user"=>NULL, "api"=>NULL, "next"=>Session::nextCheck()));
	}

}
