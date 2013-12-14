<?php

function dump_config() {
	global $configured_options;

	if (isset($configured_options)) {
		foreach ($configured_options as $name) {
			print("  $name => " . constant($name) . "\n");
		}
	}
}
