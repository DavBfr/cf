<?php

define("CF_DIR", "@CF_DIR@");
define("ROOT_DIR", dirname(__file__));

require(CF_DIR . "/cf.php");

$tpt = CorePlugin::bootstrap();
$tpt->output("index.php");
