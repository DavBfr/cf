<?php

class UserModel extends BaseUserModel {
	
	public function dologin($login, $password) {
		$bdd = Bdd::getInstance();
		
		$user = $this->simpleSelect(array($bdd->quoteIdent(self::LOGIN)."=:login"),array("login"=>$login));
		
		if ($user->isEmpty())
		return False;
		
		$hash = $user->get(self::PASSWORD);
		$pwd = new Password();
		if (! $pwd->check($password, $hash))
		return False;
		
		return $user->getId();
	}


	public function setPassword($data, $value) {
		$pwd = new Password();
		
		if ($value == $data->get(self::PASSWORD)) {
			return $value;
		}
		
		return $pwd->hash($value);
	}
	
}
