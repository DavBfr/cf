<?php

function bootstrap() {
	$conf = Config::getInstance();

	if (@$_SERVER["PATH_INFO"]) {
		Rest::handle();
	}

	$minifier = new Minifier();
	foreach($conf->get("scripts", Array()) as $script) {
		$minifier->add($script);
	}
	$minifier->add_dir(APP_DIR, APP_PATH);
	if ($conf->get("angular", false)) {
		$minifier->add_dir(CF_APP_DIR, CF_APP_PATH);
	}

	$tpt = new Template(array(
		"scripts" => $minifier->get_scripts(),
		"stylesheets" => $minifier->get_stylesheets(),
		"title" => $conf->get("title", "CF")
	));

	return $tpt;
}
