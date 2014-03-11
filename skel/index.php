<?php

define("CF_DIR", dirname(dirname(__file__)));
define("ROOT_DIR", CF_DIR . "/skel");
//define("WWW_DIR", $_SERVER['DOCUMENT_ROOT']);

require(CF_DIR . "/cf.php");

$tpt = CorePlugin::bootstrap();
$tpt->output("index.php");
