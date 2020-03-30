<?php namespace DavBfr\CF;

class HomeRest extends Rest {

	/**
	 *
	 */
	protected function getRoutes() {
		$this->addRoute("/", "GET", "home", array("description" => "Return a homepage generated from a Markdown document"));
	}


	/**
	 * @param string $mp
	 * @return bool
	 * @throws \Exception
	 */
	protected function preCheck($mp) {
		Session::ensureLoggedin();
		Session::ensureXsrfToken();
		return parent::preCheck($mp);
	}


	/**
	 * $routeProvider.when('/', {
	 *   templateUrl: cf_options.rest_path + '/home'
	 *  }).otherwise({
	 *    redirectTo: '/',
	 * });
	 *
	 * @param $r
	 * @throws \Exception
	 */
	protected function home($r) {
		$tpt = new MdTemplate();
		$tpt->outputCached("home.md");
	}

}
