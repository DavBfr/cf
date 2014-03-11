<?php

ob_start();

$configured_options = array();

function configure($key, $value) {
	global $configured_options;
	if (!defined($key)) {
		$configured_options[] = $key;
		define($key, $value);
	}
}

define("URL_SEPARATOR", "/");
configure("CF_VERSION", "1.0");
if (defined("ROOT_DIR")) {
	configure("INIT_CONFIG_DIR", ROOT_DIR . DIRECTORY_SEPARATOR . "config");
} else {
	configure("INIT_CONFIG_DIR", dirname(dirname(__file__)) . DIRECTORY_SEPARATOR . "config");
}

if (file_exists(INIT_CONFIG_DIR . DIRECTORY_SEPARATOR . "config.local.php")) {
	require(INIT_CONFIG_DIR . DIRECTORY_SEPARATOR . "config.local.php");
}

if (file_exists(INIT_CONFIG_DIR . DIRECTORY_SEPARATOR . "config.php")) {
	require_once(INIT_CONFIG_DIR . DIRECTORY_SEPARATOR . "config.php");
}

configure("CF_DIR", dirname(__file__));
configure("CF_URL", "http://cf.nfet.net");
configure("CF_PLUGINS_DIR", CF_DIR);
configure("ROOT_DIR", dirname(CF_DIR));
configure("DATA_DIR", ROOT_DIR);
configure("CONFIG_DIR", ROOT_DIR . DIRECTORY_SEPARATOR . "config");
configure("DATA_DIR", CONFIG_DIR);
configure("JCONFIG_FILE", CONFIG_DIR . DIRECTORY_SEPARATOR . "config.json");
configure("CORE_PLUGIN", "Core");
configure("WWW_DIR", ROOT_DIR . DIRECTORY_SEPARATOR . "www");
configure("WWW_PATH", "www");
configure("INDEX_PATH", "index.php");
configure("REST_PATH", INDEX_PATH);
configure("SESSION_NAME", "CF");
configure("FORCE_HTTPS", False);
configure("DEFAULT_TIMEZONE", "Europe/Paris");
configure("DEBUG", False);
configure("IS_CLI", defined("STDIN"));

if (FORCE_HTTPS && $_SERVER["HTTPS"] != "on") {
	header("Location: https://" . $_SERVER["HTTP_HOST"] . $_SERVER["REQUEST_URI"]);
	die();
}

date_default_timezone_set(DEFAULT_TIMEZONE);

function __autoload($class_name) {
	if (!class_exists($class_name)) {
		foreach(Plugins::get_plugins() as $plugin) {
			if (Plugins::get($plugin)->autoload($class_name))
				return;
		}
	}
}

require_once(CF_DIR . DIRECTORY_SEPARATOR . CORE_PLUGIN . DIRECTORY_SEPARATOR . "classes" . DIRECTORY_SEPARATOR . "Plugins.class.php");
Plugins::add(CORE_PLUGIN);
Plugins::addApp();
