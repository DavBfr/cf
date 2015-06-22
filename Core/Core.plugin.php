<?php
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
		$cli->addCommand("update", array("Cli", "update"), "Update the application");
		$cli->addCommand("clean", array("Cli", "clean"), "Clean the application cache");
	}

}
