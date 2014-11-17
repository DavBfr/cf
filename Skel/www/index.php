<?php

define("CF_DIR", "@CF_DIR@");
define("ROOT_DIR", dirname(dirname(__file__)));
define("WWW_PATH", ".");

require(CF_DIR . "/cf.php");

$tpt = CorePlugin::bootstrap();
$tpt->output("index.php");
