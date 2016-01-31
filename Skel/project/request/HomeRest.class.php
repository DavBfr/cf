<?php namespace DavBfr\CF;

class HomeRest extends Rest {

	protected function getRoutes() {
		$this->addRoute("/", "GET", "home");
	}


	protected function home($r) {
		$readme = file_get_contents(ROOT_DIR . "/README.md");
		die("<pre>" . $readme . "</pre>");
	}

}
