<?php namespace DavBfr\CF;

if (!file_exists(dirname(__file__) . "/config/paths.php"))
	die("Site not configured.");
include_once(dirname(__file__) . "/config/paths.php");

if (file_exists(dirname(__file__) . "/vendor/autoload.php"))
	include_once(dirname(__file__) . "/vendor/autoload.php");
else
	require_once(CF_DIR . "/cf.php");

$tpt = CorePlugin::bootstrap();
configure("CF_TEMPLATE", "index.php");
$tpt->outputCached(CF_TEMPLATE);
