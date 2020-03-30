<?php namespace DavBfr\CF;

class AccountRest extends Rest {

	/**
	 * @param $mp
	 * @return bool
	 * @throws \Exception
	 */
	protected function preCheck($mp) {
		Session::ensureLoggedin();
		Session::ensureXsrfToken();
		return parent::preCheck($mp);
	}


	/**
	 *
	 */
	protected function getRoutes() {
		$config = Config::getInstance();

		$this->addRoute("/partial", "GET", "get_partial");
		$this->addRoute("/", "GET", "get_info");
		if ($config->get('login.allow_change_password'))
			$this->addRoute("/password", "POST", "change_password");
		if ($config->get('login.allow_change_name'))
			$this->addRoute("/username", "POST", "change_username");
		if ($config->get('login.allow_change_email'))
			$this->addRoute("/email", "POST", "change_email");
	}


	/**
	 * @param $r
	 * @throws \Exception
	 */
	protected function get_info($r) {
		$userId = Session::Get("userid");
		$users = new UserModel();
		$user = $users->getById($userId);

		if ($user->isEmpty()) {
			Output::error("Invalid user id");
		}

		$values = $user->getValues();
		$values['gravatar'] = '00000000000000000000000000000000';

		foreach ($users->getFields() as $field) {
			if ($field->getEditor() == 'passwd') {
				unset($values[$field->getName()]);
			}

			if ($field->getEditor() == 'email') {
				$values['gravatar'] = md5(strtolower(trim($values[$field->getName()])));
			}

		}

		Output::success($values);
	}


	/**
	 * @param $r
	 * @throws \Exception
	 */
	protected function change_password($r) {
		$post = $this->jsonpost();
		Input::ensureRequest($post, array("old_pass", "new_pass"));

		$userid = Session::Get("userid");
		Logger::info("Changing password for " . $userid);
		$users = new UserModel();
		$user = $users->getById($userid);

		if ($user->isEmpty())
			Output::error("Invalid user");

		try {
			$users->changePassword($userid, $post["old_pass"], $post["new_pass"]);
			Output::success();
		} catch (\Exception $e) {
			Output::error($e->getMessage());
		}
	}


	/**
	 * @param $r
	 * @throws \Exception
	 */
	protected function change_username($r) {
		$post = $this->jsonpost();
		Input::ensureRequest($post, array("username"));

		$userid = Session::Get("userid");
		Logger::info("Changing username for " . $userid);
		$users = new UserModel();
		$user = $users->getById($userid);

		if ($user->isEmpty()) {
			Output::error("Invalid user");
		}

		$user->set(UserModel::NAME, $post["username"]);
		$user->save();
		Output::success();
	}


	/**
	 * @param $r
	 * @throws \Exception
	 */
	protected function change_email($r) {
		$post = $this->jsonpost();
		Input::ensureRequest($post, array("email"));

		$userid = Session::Get("userid");
		Logger::info("Changing username for " . $userid);
		$users = new UserModel();
		$user = $users->getById($userid);

		if ($user->isEmpty()) {
			Output::error("Invalid user");
		}

		$user->set(UserModel::EMAIL, $post["email"]);
		$user->save();
		Output::success();
	}


	/**
	 * @param array $r
	 * @throws \Exception
	 */
	protected function get_partial($r) {
		$tpt = new Template();
		$tpt->outputCached("account-page.php");
	}

}
