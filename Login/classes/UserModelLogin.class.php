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

class UserModelLogin extends BaseUserModel {

	/**
	 * @param string $login
	 * @param string $password
	 * @return bool
	 * @throws \Exception
	 */
	public function dologin($login, $password) {
		$bdd = Bdd::getInstance();

		$user = $this->simpleSelect(array($bdd->quoteIdent(self::LOGIN) . "=:login"), array("login" => $login));

		if ($user->isEmpty()) {
			Logger::debug("User $login not found");
			return false;
		}

		$hash = $user->raw(self::PASSWORD);
		$pwd = new Password();
		if (!$pwd->check($password, $hash)) {
			Logger::debug("Invalid password for $login");
			return false;
		}

		if ($user->get(self::ADMIN))
			Session::addRight("admin");

		Logger::debug("User $login authenticated");
		return $user->getId();
	}


	/**
	 * @param ModelData $data
	 * @param string $value
	 * @return string
	 * @throws \Exception
	 */
	public function setPasswordField($data, $value) {
		$current = $data->raw(self::PASSWORD);
		$hash = $this->getPasswordField($data, $current);

		if ($hash == $value) { // No change
			return $current;
		}

		$pwd = new Password();
		return $pwd->hash($value);
	}


	/**
	 * @param ModelData $data
	 * @param string $value
	 * @return string
	 * @throws \Exception
	 */
	public function getPasswordField($data, $value) {
		if ($value === null)
			return null;

		return str_repeat('*', 13);
	}


	/**
	 * @param int $userId
	 * @param string $old_password
	 * @param string $new_password
	 * @throws \Exception
	 */
	public function changePassword($userId, $old_password, $new_password) {
		$user = $this->getById($userId);
		$pwd = new Password();

		if (!$pwd->check($old_password, $user->raw(self::PASSWORD))) {
			throw new \Exception("Unable to change password. Please ensure you have entered the current password correctly.");
		}

		$ret = $pwd->strengthCheck($new_password, $old_password, $user->get(self::LOGIN));
		if ($ret !== true) {
			throw new \Exception("Unable to change password. $ret");
		}

		$user->set(self::PASSWORD, $new_password);
		$user->save();
		Logger::debug("Password updated.");
	}


	/**
	 * @return ModelData
	 * @throws \Exception
	 */
	public function newRow() {
		$user = parent::newRow();
		$user->set(self::CREATION, time());
		return $user;
	}
}
