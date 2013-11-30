<?php

class Login extends AbstractLogin {
	
	protected function dologin($username, $password) {
		return 0;
	}

	protected function apilogin($token) {
		return false;
	}

}
