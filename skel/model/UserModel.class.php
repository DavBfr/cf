<?php

class UserModel extends Model {
	
	protected function getTable() {
		return array("USERS", array(
			"id" => array("type"=>"int", "primary"=>true, "edit"=>false, "autoincrement"=>true),
			"login" => array("type"=>"text", "caption"=>"Utilisateur", "list"=>true),
			"password" => array("type"=>"text", "caption"=>"Mot de passe", "list"=>false),
		));
	}

}
