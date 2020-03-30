{
	"title": "CF",
	"type": "object",
	"properties": {
		"title": {
			"type": "string",
			"description": "Site title",
			"default": "CF"
		},
		"description": {
			"type": "string",
			"description": "Site description",
			"default": "CF",
			"media": {
				"type": "text/html"
			}
		},
		"plugins": {
			"type": "array",
			"uniqueItems": true,
			"default": [],
			"items": {
				"type": "string",
				"title": "Plugin",
				"enum": ["<?php
$plugins = array();
foreach(array(PLUGINS_DIR, CF_PLUGINS_DIR) as $dir) {
	if (is_dir($dir)) {
		if ($dh = opendir($dir)) {
			while (($file = readdir($dh)) !== false) {
				if (is_dir($dir . DIRECTORY_SEPARATOR . $file) && $file[0] != "." && $file != CORE_PLUGIN) {
					$plugins[] = $file;
				}
			}
			closedir($dh);
		}
	}
}
echo implode('","', $plugins);
				?>"]
			}
		},
		"scripts": {
			"type": "array",
			"format": "table",
			"default": [],
			"uniqueItems": true,
			"items": {
				"type": "string",
				"title": "Script"
			}
		},
		"model": {
			"type": "array",
			"format": "table",
			"default": [],
			"title": "Model",
			
			"items": {
				"type": "object",
				"title": "Table",
				"headerTemplate": "{{ i1 }}",
  			"format": "grid"
			}
		}
	}
}
