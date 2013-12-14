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
configure("ROOT_DIR", dirname(CF_DIR));
configure("CONFIG_DIR", ROOT_DIR . DIRECTORY_SEPARATOR . "config");
configure("DATA_DIR", CONFIG_DIR);
configure("JCONFIG_FILE", CONFIG_DIR . DIRECTORY_SEPARATOR . "config.json");

configure("CF_INCLUDE_DIR", CF_DIR . DIRECTORY_SEPARATOR . "includes");
configure("CF_WWW_DIR", CF_DIR . DIRECTORY_SEPARATOR . "www");
configure("CF_WWW_PATH", "cf" . URL_SEPARATOR . "www");
configure("CF_CLASSES_DIR", CF_DIR . DIRECTORY_SEPARATOR . "classes");
configure("CF_VENDOR_DIR", CF_WWW_DIR . DIRECTORY_SEPARATOR . "vendor");
configure("CF_VENDOR_PATH", CF_WWW_PATH . URL_SEPARATOR . "vendor");
configure("CF_APP_DIR", CF_WWW_DIR . DIRECTORY_SEPARATOR . "app");
configure("CF_APP_PATH", CF_WWW_PATH . URL_SEPARATOR . "app");

configure("INCLUDE_DIR", ROOT_DIR  . DIRECTORY_SEPARATOR . "includes");
configure("WWW_DIR", ROOT_DIR . DIRECTORY_SEPARATOR . "www");
configure("WWW_PATH", "www");
configure("CLASSES_DIR", ROOT_DIR . DIRECTORY_SEPARATOR . "classes");
configure("MODEL_DIR", ROOT_DIR . DIRECTORY_SEPARATOR . "model");
configure("TEMPLATES_DIR", ROOT_DIR . DIRECTORY_SEPARATOR . "templates");
configure("TEMPLATES_PATH", WWW_PATH . DIRECTORY_SEPARATOR . "partials");
configure("VENDOR_DIR", WWW_DIR . DIRECTORY_SEPARATOR . "vendor");
configure("VENDOR_PATH", WWW_PATH . URL_SEPARATOR . "vendor");
configure("MEDIA_PATH", WWW_PATH . URL_SEPARATOR . "media");
configure("MEDIA_DIR", WWW_PATH . URL_SEPARATOR . "media");
configure("APP_DIR", WWW_DIR . DIRECTORY_SEPARATOR . "app");
configure("APP_PATH", WWW_PATH . URL_SEPARATOR . "app");
configure("INDEX_PATH", "index.php");
configure("REST_PATH", INDEX_PATH);
configure("REQUEST_DIR", ROOT_DIR . DIRECTORY_SEPARATOR . "request");

configure("SESSION_NAME", "CF");
configure("FORCE_HTTPS", False);
configure("DEFAULT_TIMEZONE", "Europe/Paris");
configure("DEBUG", False);
configure("IS_CLI", defined("STDIN"));

set_include_path(get_include_path() . URL_SEPARATOR . VENDOR_DIR);

if (FORCE_HTTPS && $_SERVER["HTTPS"] != "on") {
	header("Location: https://" . $_SERVER["HTTP_HOST"] . $_SERVER["REQUEST_URI"]);
	die();
}

date_default_timezone_set(DEFAULT_TIMEZONE);

if ($handle = opendir(CF_INCLUDE_DIR)) {
	while (false !== ($entry = readdir($handle))) {
		if (substr($entry, strlen($entry) - 8) == ".inc.php") {
			require_once(CF_INCLUDE_DIR . DIRECTORY_SEPARATOR . $entry);
		}
	}
	closedir($handle);
}
if (is_dir(INCLUDE_DIR) && $handle = opendir(INCLUDE_DIR)) {
	while (false !== ($entry = readdir($handle))) {
		if (substr($entry, strlen($entry) - 8) == ".inc.php") {
			require_once(INCLUDE_DIR . DIRECTORY_SEPARATOR . $entry);
		}
	}
	closedir($handle);
}
unset($handle);
unset($entry);
