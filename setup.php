<?php
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

if (!defined("ROOT_DIR"))
	define("ROOT_DIR", getcwd());

require_once(dirname(__file__) . DIRECTORY_SEPARATOR . "cf.php");

if (!IS_CLI)
	die("Not running from CLI");

$logger = Logger::getInstance();
$logger->setLevel(Logger::WARNING);

$conf = Config::getInstance();
if (file_exists(CONFIG_DIR."/config.json")) {
	$conf->append(CONFIG_DIR."/config.json");
}
foreach($conf->get("plugins", Array()) as $plugin) {
	Plugins::add($plugin);
}
Plugins::add("Skel");
foreach (array_reverse(Plugins::findAll(CorePlugin::config)) as $filename) {
	$conf->append($filename);
}
Plugins::dispatchAllReversed("config", $conf);

$cli = new Cli($_SERVER['argv']);
Plugins::dispatchAll("cli", $cli);
$cli->handle($cli->getCommand(), $cli->getArguments());
die();
