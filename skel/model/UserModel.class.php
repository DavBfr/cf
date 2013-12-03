<?php

class UserModel extends Model {

	public function dologin($login, $password) {
		$bdd = Bdd::getInstance();
		return $this->simpleSelect(array(
			$bdd->quoteIdent("login") . "=:login",
			$bdd->quoteIdent("password") . "=SHA1(:password)"),
		array(
			"login" => $login,
			"password" => $password
		));
	}

}
