<?php namespace DavBfr\CF;

class TokenRest extends Crud {

	/**
	 * @param $mp
	 * @return bool
	 * @throws \Exception
	 */
	protected function preCheck($mp) {
		Session::ensureLoggedin();
		Session::ensureXsrfToken();
		Session::ensureRight("admin");
		return parent::preCheck($mp);
	}


	/**
	 * @return Model|TokenModel
	 */
	protected function getModel() {
		return new TokenModel();
	}

}
