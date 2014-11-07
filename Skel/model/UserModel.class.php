<?php

class UserModel extends BaseUserModel {

	public function dologin($login, $password) {
		$bdd = Bdd::getInstance();
		return $this->simpleSelect(array(
			$bdd->quoteIdent(self::LOGIN) . "=:login",
			$bdd->quoteIdent(self::PASSWORD) . "=:password"),
		array(
			"login" => $login,
			"password" => sha1($password)
		));
	}

}
