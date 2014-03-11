#!/usr/bin/env php
<?php

define("ROOT_DIR", getcwd());
require_once(dirname(__file__) . DIRECTORY_SEPARATOR . "cf.php");

if (!IS_CLI)
	die("Not running from CLI");

$cli = new Cli($_SERVER['argv']);

$cli->addCommand("model:export", array("Model", "export"), "Export database model to sql statements");
$cli->addCommand("model:import", array("Model", "import"), "Import database model to json format");
$cli->addCommand("model:create:classes", array("Model", "createClassesFromConfig"), "Create php classes from json configuration");

//$cli->addCommand("crud:create", array("Crud", "createClassesFromConfig"), "Create php classes from json configuration");

$cli->handle($cli->getCommand(), $cli->getArguments());
die();
