#!/usr/bin/env php
<?php

define("DEBUG", true);
define("ROOT_DIR", getcwd());
require_once(dirname(__file__) . DIRECTORY_SEPARATOR . "cf.php");

if (!IS_CLI)
	die("Not running from CLI");

Plugins::addAll(PLUGINS_DIR);
Plugins::addAll(CF_PLUGINS_DIR);
$cli = new Cli($_SERVER['argv']);
Plugins::dispatchAll("cli", $cli);
$cli->handle($cli->getCommand(), $cli->getArguments());
die();
