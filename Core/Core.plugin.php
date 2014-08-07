<?php

configure("WWW_DIR", ROOT_DIR . DIRECTORY_SEPARATOR . "www");
configure("WWW_PATH", "www");
configure("INDEX_PATH", "index.php");
configure("REST_PATH", INDEX_PATH);
configure("DATA_DIR", ROOT_DIR . DIRECTORY_SEPARATOR . "data");
configure("DOCUMENT_ROOT", str_replace($_SERVER["SCRIPT_NAME"], '', $_SERVER["SCRIPT_FILENAME"]));
configure("MEMCACHE_PREFIX", "CF");
configure("MEMCACHE_LIFETIME", 10800);
configure("SESSION_NAME", "CF");
configure("ERROR_TEMPLATE", "error.php");
configure("CACHE_DIR", DATA_DIR . DIRECTORY_SEPARATOR . "cache");
configure("WWW_CACHE_DIR", WWW_DIR . DIRECTORY_SEPARATOR . "cache");
configure("LANG_DEFAULT", "en_US");
configure("LANG_AUTOLOAD", true);
configure("LANG_AUTODETECT", true);
configure("JCONFIG_FILE", CONFIG_DIR . DIRECTORY_SEPARATOR . "config.json");
configure("CF_URL", "http://cf.nfet.net");

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


	public function install() {
		global $configured_options;

		Cli::pln(" * Create folders");
		foreach ($configured_options as $key) {
			if (substr($key, -4) == "_DIR") {
				System::ensureDir(constant($key));
			}
		}
	}


	public function cli($cli) {
		$cli->addCommand("core:config", array("Cli", "configuration"), "Get framework configuration");
		$cli->addCommand("core:version", array("Cli", "version"), "Get framework version");
		$cli->addCommand("install", array("Cli", "install"), "Install the application");
	}

}
