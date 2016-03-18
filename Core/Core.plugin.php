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

configure("WWW_DIR", ROOT_DIR . DIRECTORY_SEPARATOR . "www");

configure("DATA_DIR", ROOT_DIR . DIRECTORY_SEPARATOR . "data");
configure("DOCUMENT_ROOT", str_replace($_SERVER["SCRIPT_NAME"], '', $_SERVER["SCRIPT_FILENAME"]));
configure("ALLOW_DOCUMENT_ROOT", true);
if (substr(WWW_DIR, 0, strlen(DOCUMENT_ROOT)) == DOCUMENT_ROOT )
	configure("WWW_PATH", str_replace(DOCUMENT_ROOT, '', WWW_DIR));
else
	configure("WWW_PATH", "www");
configure("INDEX_PATH", WWW_PATH."/index.php");
configure("REST_PATH", array_key_exists('HTTP_MOD_REWRITE', $_SERVER)?WWW_PATH."/r":INDEX_PATH);
configure("MEMCACHE_PREFIX", "CF");
configure("MEMCACHE_LIFETIME", 10800);
configure("MEMCACHE_ENABLED", false);
configure("CACHE_ENABLED", !DEBUG);
configure("JSON_HEADER", !DEBUG || (isset($_SERVER["HTTP_X_REQUESTED_WITH"]) && $_SERVER["HTTP_X_REQUESTED_WITH"] == "XMLHttpRequest"));
configure("SESSION_NAME", "CF");
configure("SESSION_TIMEOUT", ini_get("session.gc_maxlifetime"));
configure("SESSION_REGENERATE", SESSION_TIMEOUT);
configure("API_TOKEN_HEADER", "cf-token");
configure("ERROR_TEMPLATE", "error.php");
configure("CACHE_DIR", DATA_DIR . DIRECTORY_SEPARATOR . "cache");
configure("WWW_CACHE_DIR", WWW_DIR . DIRECTORY_SEPARATOR . "cache");
configure("LANG_DEFAULT", "en_US");
configure("LANG_AUTOLOAD", true);
configure("LANG_AUTODETECT", true);
configure("CF_URL", "https://github.com/DavBfr/cf");
configure("CF_AUTHOR", "David PHAM-VAN");
configure("CF_EMAIL", "dev.nfet.net@gmail.com");

class CorePlugin extends Plugins {
	const config = "config/config.json";
	
	public static function loadConfig() {
		$conf = Config::getInstance();

		$memcache = new MemCache();
		if (array_key_exists("JCONFIG_FILE", $memcache)) {
			$conf->setData($memcache["JCONFIG_FILE"]);
			Logger::debug("Config loaded from cache");
			foreach($conf->get("plugins", Array()) as $plugin) {
				Plugins::add($plugin);
			}
		} else {
			$cache = Cache::Priv(self::config, ".php");
			if ($cache->check()) {
				if (file_exists(ROOT_DIR."/composer.json")) {
					$conf->append(ROOT_DIR."/composer.json", false, "composer");
				}
				if (file_exists(CONFIG_DIR."/config.json")) {
					$conf->append(CONFIG_DIR."/config.json");
				}
				if (file_exists(CONFIG_DIR."/config.local.json")) {
					$conf->append(CONFIG_DIR."/config.local.json");
				}
				$confsave = $conf->getData();
				foreach($conf->get("plugins", Array()) as $plugin) {
					Plugins::add($plugin);
				}
				foreach (array_reverse(Plugins::findAll(self::config)) as $filename) {
					$conf->append($filename);
				}
				Plugins::dispatchAllReversed("config", $conf);
				$conf->merge($confsave);
				ArrayWriter::toFile($conf->getData(), $cache->getFilename());
			} else  {
				$conf->setData(ArrayWriter::fromFile($cache->getFilename()));
				foreach($conf->get("plugins", Array()) as $plugin) {
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
				"description" => $conf->get("description", NULL),
				"favicon" => $conf->get("favicon", NULL),
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


	public function preupdate() {
		global $configured_options;

		Cli::pinfo(" * Create folders");
		foreach ($configured_options as $key) {
			if (substr($key, -4) == "_DIR") {
				System::ensureDir(constant($key));
			}
		}
	}


	public function update() {
		if (Cli::getOption("a"))
			$cf_dir = "\"".realpath(CF_DIR)."\"";
		else
			$cf_dir = "ROOT_DIR . \"/".System::relativePath(System::absPath(ROOT_DIR), System::absPath(CF_DIR))."\"";

		Cli::pinfo(" * Create paths.php");
		Logger::Debug("CF dir is $cf_dir");
		Logger::Debug("ROOT dir is ". ROOT_DIR);
		$content = "<?php // DO NOT MODIFY THIS FILE, IT IS GENERATED BY setup update SCRIPT\n\n";
		$content .= "@define(\"ROOT_DIR\", \"".ROOT_DIR."\");\n";
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
		$cli->addCommand("core:config", array(__NAMESPACE__ . "\\Cli", "configuration"), "Get framework configuration");
		$cli->addCommand("core:jconfig", array(__NAMESPACE__ . "\\Cli", "jconfig"), "Get framework configuration from merged json files");
		$cli->addCommand("core:export-conf", array(__NAMESPACE__ . "\\Cli", "exportconf"), "Export framework configuration");
		$cli->addCommand("core:version", array(__NAMESPACE__ . "\\Cli", "version"), "Get framework version");
		$cli->addCommand("install", array(__NAMESPACE__ . "\\Cli", "install"), "Install the application");
		$cli->addCommand("update", array(__NAMESPACE__ . "\\Cli", "update"), "Update the application");
		$cli->addCommand("clean", array(__NAMESPACE__ . "\\Cli", "clean"), "Clean the application cache");
		if (class_exists("PHPUnit_Framework_TestSuite", true)) {
			$cli->addCommand("test", array(__NAMESPACE__ . "\\UnitTest", "runtests"), "Run unit testing");
		}
	}

}
