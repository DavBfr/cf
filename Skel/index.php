<?php

include_once(dirname(__file__) . "/config/paths.php");
require(CF_DIR . "/cf.php");

$tpt = CorePlugin::bootstrap();
echo $tpt->output("index.php");
