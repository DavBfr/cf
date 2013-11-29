<?php

function output_json($object) {
	if (!DEBUG)
		header("Content-Type: text/json");
	else
		header("Content-Type: text/plain");
	
	$content = ob_get_contents();
	ob_end_clean();
	if (DEBUG && is_array($object) && strlen($content) > 0) {
		$object["__debug__"] = $content;
	}
	echo json_encode($object);
	die();
}
