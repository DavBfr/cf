<?php

class LoginRest extends AbstractLogin {
	
	protected function login($username, $password) {
		return 0;
	}

	protected function apiLogin($token) {
		return false;
	}

}
