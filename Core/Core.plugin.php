<?php

class CorePlugin extends Plugins {

	public static function bootstrap() {
		ErrorHandler::Init("ErrorHandler");
		$conf = Config::getInstance();
		foreach($conf->get("plugins", Array()) as $plugin) {
			Plugins::add($plugin);
		}

		if (array_key_exists("PATH_INFO", $_SERVER) && $_SERVER["PATH_INFO"]) {
			Rest::handle();
		}

		if (class_exists("Minifier"))
			$resources = new Minifier();
		else
			$resources = new Resources();

		Plugins::dispatchAll("resources", $resources);

		foreach($conf->get("scripts", Array()) as $script) {
			$resources->add($script);
		}
		foreach(Plugins::findAll("www/app") as $dir) {
			$resources->addDir($dir);
		}

		$tpt = new Template(array(
				"scripts" => $resources->getScripts(),
				"stylesheets" => $resources->getStylesheets(),
				"title" => $conf->get("title", "CF")
		));

		return $tpt;
	}


	public static function getBaseline() {
		return "CF " . CF_VERSION . " ⠶ PHP " . PHP_VERSION . (isset($_SERVER["SERVER_SOFTWARE"]) ? " ⠶ " . $_SERVER["SERVER_SOFTWARE"] : "");
	}


	public static function info() {
		global $configured_options;

		$info = '<div class="well"><h1><a href="'.CF_URL.'">CF '.CF_VERSION.'</a></h1></div>';
		if (isset($configured_options)) {
			$info .= '<h2>CF configuration</h2><table class="table table-bordered table-striped table-condensed"><tbody>';
			foreach($configured_options as $key) {
				if ($key == 'DBPASSWORD')
					$val = "****";
				else
					$val = constant($key);
				$info .= '<tr><th>'.$key.'</th><td>'.$val.'</td></tr>';
			}
			$info .= '</tbody></table>';
		}
		$info .= '<h2>Server configuration</h2><table class="table table-bordered table-striped table-condensed"></tbody>';
		foreach($_SERVER as $key=>$val) {
			if ($key == 'PHP_AUTH_PW')
				$val = '*****';
				
			$info .= '<tr><th>'.$key.'</th><td>'.$val.'</td></tr>';
		}
		$info .= '</tbody></table>';

		return $info;
	}

}
