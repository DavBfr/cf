<?php namespace DavBfr\CF;

class TokenModelLogin extends BaseTokenModel {

	/**
	 * @return mixed
	 * @throws \Exception
	 */
	public function newRow() {
		$row = parent::newRow();
		$pwd = new Password();
		$row->set(self::TOKEN, substr($pwd->hash($pwd->getRandomBytes(32)), 7, 16));
		return $row;
	}


	/**
	 * @param string $token
	 * @return null
	 * @throws \Exception
	 */
	public static function checkToken($token) {
		$tokens = new self();
		$tk = $tokens->getBy(self::TOKEN, $token);
		if (!$tk->isEmpty()) {
			if ($tk->get(self::ACTIVE)) {
				return $tk->get(self::ID);
			}
		}

		return null;
	}


	/**
	 * Ensure that a given token exists
	 * @param string $value
	 * @param string $name
	 * @param bool $active
	 * @throws \Exception
	 */
	public static function create($value, $name = null, $active = true) {
		$tokens = new self();
		$tk = $tokens->getBy(self::TOKEN, $value);

		if ($tk->isEmpty()) {
			$tk = $tokens->newRow();
			$tk->set(self::TOKEN, $value);
			$tk->set(self::NAME, $name === null ? $value : $name);
			$tk->set(self::ACTIVE, $active);
			$tk->save();
		}
	}

}
