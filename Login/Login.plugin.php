<?php namespace DavBfr\CF;
/**
 * Copyright (C) 2013-2014 David PHAM-VAN
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

class LoginPlugin extends Plugins {

	/**
	 * @param Cli $cli
	 */
	public function cli($cli) {
		$cli->addCommand("login:token", array($this, "token"), "Create a new token");
		$cli->addCommand("login:user", array($this, "user"), "Create a new user");
	}


	/**
	 * @throws \Exception
	 */
	public function token() {
		$token = Cli::addOption('token', null, 'Token to create');
		$name = Cli::addOption('name', 'default', 'Token name');
		Cli::enableHelp();

		if ($token == null) {
			Cli::pfatal("Missing token value");
		}

		TokenModel::create($token, $name);
	}


	/**
	 * @throws \Exception
	 */
	public function user() {
		$login = Cli::addOption('login', null, 'User\'s login');
		$password = Cli::addOption('password', null, 'User\'s password');
		$name = Cli::addOption('name', null, 'User name');
		$email = Cli::addOption('email', null, 'Email address');
		$admin = Cli::addSwitch('admin', 'Is administrator');
		Cli::enableHelp();

		if ($login == null) {
			Cli::pfatal("Missing login");
		}

		$model = new UserModel();
		$ln = $model->getBy(UserModel::LOGIN, $login);
		if ($ln->isEmpty()) {
			$ln = $model->newRow();
			$ln->set(UserModel::LOGIN, $login);
			if (!$password) {
				$pwd = new Password();
				$password = $pwd->hash($pwd->getRandomBytes(32));
			}
			if (!$name)
				$name = $login;

		}
		if ($name) $ln->set(UserModel::NAME, $name);
		if ($password) $ln->set(UserModel::PASSWORD, $password);
		if ($email) $ln->set(UserModel::EMAIL, $email);
		$ln->set(UserModel::ADMIN, $admin);
		$ln->save();
	}


	/**
	 *
	 * @throws \Exception
	 */
	public function postinstall() {
		Cli::pinfo(" * Create default database");
		$model = new UserModel();
		if (!is_subclass_of($model, "DavBfr\CF\UserModelLogin"))
			Cli::pfatal("UserModel must be a subclass of UserModelLogin");
		$user = $model->newRow();
		$user->set(UserModel::LOGIN, "admin");
		$user->set(UserModel::PASSWORD, "admin");
		$user->set(UserModel::NAME, "Administrator");
		$user->set(UserModel::ADMIN, true);
		$user->save();
	}


	/**
	 * @param $tpt
	 * @return null|string
	 * @throws \Exception
	 */
	public function index($tpt) {
		$conf = Config::getInstance();
		if ($conf->get("login.full_site", true) && !Session::isLogged()) {
			Session::delete();
			return "login.php";
		}
		return null;
	}


	/**
	 * @param $token
	 * @return bool
	 * @throws \Exception
	 */
	public function token_login($token) {
		return TokenModel::checkToken($token) !== null;
	}


	/**
	 * @param Resources $res
	 */
	public function resources($res) {
		$res->add("account.css");
	}

}
