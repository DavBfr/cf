<?php namespace DavBfr\CF;
/**
 * Copyright (C) 2013-2015 David PHAM-VAN
 *
 * This library is free software; you can redistribute it and/or
 * modify it under the terms of the GNU Lesser General Public
 * License as published by the Free Software Foundation; either
 * version 2.1 of the License, or (at your option) any later version.
 *
 * This library is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the GNU
 * Lesser General Public License for more details.
 *
 * You should have received a copy of the GNU Lesser General Public
 * License along with this library; if not, write to the Free Software
 * Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301, USA.
 **/

use PHPUnit_Framework_TestSuite;

Options::set("WWW_DIR", ROOT_DIR . DIRECTORY_SEPARATOR . "www", "Public webpages home");

Options::set("DATA_DIR", ROOT_DIR . DIRECTORY_SEPARATOR . "data", "Data directory");
Options::set("DOCUMENT_ROOT", str_replace($_SERVER["SCRIPT_NAME"], '', $_SERVER["SCRIPT_FILENAME"]), "Root directory under which the current script is executing");
Options::set("ALLOW_DOCUMENT_ROOT", true);
if (substr(WWW_DIR, 0, strlen(DOCUMENT_ROOT)) == DOCUMENT_ROOT)
	Options::set("WWW_PATH", str_replace(DOCUMENT_ROOT, '', WWW_DIR), "Website relative URL");
else
	Options::set("WWW_PATH", "www", "Website relative url");
Options::set("INDEX_PATH", WWW_PATH . "/index.php", "Main webpage URL");
Options::set("REST_PATH", array_key_exists('HTTP_MOD_REWRITE', $_SERVER) ? WWW_PATH . "/api" : INDEX_PATH, "Jaon Rest API URL");
Options::set("MEMCACHE_PREFIX", "CF");
Options::set("MEMCACHE_LIFETIME", 10800);
Options::set("MEMCACHE_ENABLED", false, "Enable memory cache");
Options::set("CACHE_ENABLED", !DEBUG, "Enable caching");
Options::set("CACHE_TIME", 86400, "Cache expire time");
Options::set("MESSAGE_QUEUE", 92873, "Message Queue ID");
Options::set("JSON_HEADER", !DEBUG || (isset($_SERVER["HTTP_X_REQUESTED_WITH"]) && $_SERVER["HTTP_X_REQUESTED_WITH"] == "XMLHttpRequest"));
Options::set("SESSION_NAME", "CF", "Cookie Name seen in the User Agent");
Options::set("SESSION_TIMEOUT", ini_get("session.gc_maxlifetime"), "Cookie Life Time");
Options::set("SESSION_REGENERATE", SESSION_TIMEOUT, "Time to regenerate the cookie");
Options::set("SESSION_PATH", ini_get("session.cookie_path"), "Cookie path");
Options::set("SESSION_DOMAIN", ini_get("session.cookie_domain"), "Cookie domain name");
Options::set("API_TOKEN_HEADER", "cf-token", "Api token name");
Options::set("ERROR_TEMPLATE", "error.php", "Error template");
Options::set("CACHE_DIR", DATA_DIR . DIRECTORY_SEPARATOR . "cache", "Cache directory");
Options::set("WWW_CACHE_DIR", WWW_DIR . DIRECTORY_SEPARATOR . "cache", "Public Cache URL");
Options::set("LANG_DEFAULT", "en_US", "Default language");
Options::set("LANG_AUTOLOAD", true, "Load locale file automatically");
Options::set("LANG_AUTODETECT", true, "Try to autodetect user language");
Options::set("CF_URL", "https://github.com/DavBfr/cf");
Options::set("CF_AUTHOR", "David PHAM-VAN");
Options::set("CF_EMAIL", "dev.nfet.net@gmail.com");

class CorePlugin extends Plugins {
	const config = "config";

	public static function loadConfig() {
		$conf = Config::getInstance();

		$memcache = new MemCache();
		if (array_key_exists("JCONFIG_FILE", $memcache)) {
			$conf->setData($memcache["JCONFIG_FILE"]);
			Logger::debug("Config loaded from cache");
			foreach($conf->get("plugins", array()) as $plugin) {
				Plugins::add($plugin);
			}
		} else {
			$cache = Cache::Priv(self::config, ".php");
			if ($cache->check()) {
				if (file_exists(ROOT_DIR . "/composer.json")) {
					$conf->append(ROOT_DIR . "/composer.json", false, "composer");
				}
				if (file_exists(CONFIG_DIR . "/config.json")) {
					$conf->append(CONFIG_DIR . "/config.json");
				}
				if (file_exists(CONFIG_DIR . "/config.local.json")) {
					$conf->append(CONFIG_DIR . "/config.local.json");
				}
				foreach(glob(CONFIG_DIR . "/*.json") as $file) {
					$bn = basename($file);
					if (substr($bn, 0, 7) != "config.") {
						$conf->append($file, false, substr($bn, 0, strlen($bn) - 5));
					}
				}
				if (DEBUG && file_exists(CONFIG_DIR . "/config.debug.json")) {
					$conf->append(CONFIG_DIR . "/config.debug.json");
				}
				$confsave = $conf->getData();
				foreach($conf->get("plugins", array()) as $plugin) {
					Plugins::add($plugin);
				}
				foreach (array_reverse(Plugins::findAll(self::config)) as $dirname) {
					if (file_exists($dirname . "/config.json")) {
						$conf->append($dirname . "/config.json");
					}
					foreach(glob($dirname . "/*.json") as $file) {
						$bn = basename($file);
						if (substr($bn, 0, 7) != "config.") {
							$conf->append($file, false, substr($bn, 0, strlen($bn) - 5));
						}
					}
				}
				Plugins::dispatchAllReversed("config", $conf);
				$conf->merge($confsave);
				ArrayWriter::toFile($conf->getData(), $cache->getFilename());
			} else  {
				$conf->setData(ArrayWriter::fromFile($cache->getFilename()));
				foreach($conf->get("plugins", array()) as $plugin) {
					Plugins::add($plugin);
				}
			}
			$memcache["JCONFIG_FILE"] = $conf->getData();
		}
	}


	public static function bootstrap() {
		ErrorHandler::Init(__NAMESPACE__ . "\\ErrorHandler");

		self::loadConfig();

		if (array_key_exists("PATH_INFO", $_SERVER) && $_SERVER["PATH_INFO"]) {
			Rest::handle();
		}

		$conf = Config::getInstance();
		$tpt = new TemplateRes(array(
				"title" => $conf->get("title", "CF " . CF_VERSION),
				"description" => $conf->get("description", null),
				"favicon" => $conf->get("favicon", null),
				"baseline" => $conf->get("baseline", self::getBaseline()),
		));

		foreach(Plugins::dispatchAll("index", $tpt) as $index) {
			if ($index !== null)
				$tpt->output($index);
		}

		return $tpt;
	}


	public static function getBaseline() {
		return "CF " . CF_VERSION . " ⠶ PHP " . PHP_VERSION . (isset($_SERVER["SERVER_SOFTWARE"]) ? " ⠶ " . $_SERVER["SERVER_SOFTWARE"] : "");
	}


	public static function info() {
		$info = '<div class="well"><h1><a href="' . CF_URL . '">CF ' . CF_VERSION . '</a></h1></div>';
		$info .= '<h2>CF configuration</h2><table class="table table-bordered table-striped table-condensed"><tbody>';
		foreach(Options::getAll() as $key => $val) {
			if ($key == "DBPASSWORD")
				$val = "****";
			$info .= '<tr><th>' . $key . '</th><td>' . $val . '</td></tr>';
		}
		$info .= '</tbody></table>';
		$info .= '<h2>Server configuration</h2><table class="table table-bordered table-striped table-condensed"></tbody>';
		foreach($_SERVER as $key => $val) {
			if ($key == 'PHP_AUTH_PW')
				$val = '*****';

			$info .= '<tr><th>' . $key . '</th><td>' . $val . '</td></tr>';
		}
		$info .= '</tbody></table>';

		return $info;
	}


	public function preupdate() {
		Cli::pinfo(" * Create folders");
		foreach (Options::getAll() as $key => $val) {
			if (substr($key, -4) == "_DIR") {
				System::ensureDir($val);
			}
		}
	}


	public function update() {
		if (Cli::getOption("a"))
			$cf_dir = "\"" . realpath(CF_DIR) . "\"";
		else
			$cf_dir = "ROOT_DIR . \"/" . System::relativePath(System::absPath(ROOT_DIR), System::absPath(CF_DIR)) . "\"";

		Cli::pinfo(" * Create paths.php");
		Logger::Debug("CF dir is $cf_dir");
		Logger::Debug("ROOT dir is " . ROOT_DIR);
		$content = "<?php // DO NOT MODIFY THIS FILE, IT IS GENERATED BY setup update SCRIPT\n\n";
		$content .= "@define(\"ROOT_DIR\", \"" . ROOT_DIR . "\");\n";
		$content .= "@define(\"CF_DIR\", $cf_dir);\n";
		file_put_contents(CONFIG_DIR . "/paths.php", $content);
	}


	public function clean() {
		System::rmtree(CACHE_DIR);
		System::rmtree(WWW_CACHE_DIR);
	}


	public function cli($cli) {
		if (!IS_PHAR && !ini_get("phar.readonly")) {
			$cli->addCommand("core:phar", array(__NAMESPACE__ . "\\Cli", "phar"), "Build cf.phar archive");
		}
		$cli->addCommand("config", array(__NAMESPACE__ . "\\Cli", "configuration"), "Get framework configuration");
		$cli->addCommand("jconfig", array(__NAMESPACE__ . "\\Cli", "jconfig"), "Get framework configuration from merged json files");
		$cli->addCommand("version", array(__NAMESPACE__ . "\\Cli", "version"), "Get framework version");
		$cli->addCommand("install", array(__NAMESPACE__ . "\\Cli", "install"), "Install the application");
		$cli->addCommand("update", array(__NAMESPACE__ . "\\Cli", "update"), "Update the application");
		$cli->addCommand("clean", array(__NAMESPACE__ . "\\Cli", "clean"), "Clean the application cache");
		$cli->addCommand("mq", array(__NAMESPACE__ . "\\MessageQueue", "process"), "Process the application message queue");
		if (class_exists("PHPUnit_Framework_TestSuite", true)) {
			$cli->addCommand("test", array(__NAMESPACE__ . "\\UnitTest", "runtests"), "Run unit testing");
		}
	}

}
