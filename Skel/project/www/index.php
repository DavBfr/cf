<?php namespace DavBfr\CF;

include_once(dirname(dirname(__file__)) . "/config/paths.php");

if (file_exists(dirname(dirname(__file__)) . "/vendor/autoload.php"))
	include_once(dirname(dirname(__file__)) . "/vendor/autoload.php");
else
	require_once(CF_DIR . "/cf.php");

$tpt = CorePlugin::bootstrap();
$tpt->output("index.php");
