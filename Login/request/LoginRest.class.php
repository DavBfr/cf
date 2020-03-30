<?php namespace DavBfr\CF;
/**
 * Copyright (C) 2013-2016 David PHAM-VAN
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License
 * as published by the Free Software Foundation; version 2
 * of the License.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301, USA.
 **/

class LoginRest extends AbstractLogin {

	/**
	 * @param $username
	 * @param $password
	 * @return bool
	 * @throws \Exception
	 */
	protected function login($username, $password) {
		$users = new UserModel();
		$userid = $users->dologin($username, $password);

		if ($userid === false)
			ErrorHandler::error(401, "Invalid username or password");

		return $userid;
	}


	/**
	 * @param string $token
	 * @return bool
	 * @throws \Exception
	 */
	protected function apiLogin($token) {
		$conf = Config::getInstance();

		if (!$conf->get("login.api", false))
			return false;

		$tokens = new TokenModel();
		$tk = $tokens->getBy(TokenModel::TOKEN, $token);
		if (!$tk->isEmpty()) {
			if ($tk->get(TokenModel::ACTIVE)) {
				return $tk->get(TokenModel::ID);
			}
		}

		return false;
	}

}
