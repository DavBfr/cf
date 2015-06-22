<?php

include_once(dirname(__file__) . "/config/paths.php");
require(CF_DIR . "/cf.php");

$tpt = CorePlugin::bootstrap();
$tpt->output("index.php");
