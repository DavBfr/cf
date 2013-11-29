<?php

class Template {
	private $params;
	
	public function __construct($params=Array()) {
		$this->params=array_merge($this->get_defaults(), $params);
	}
	
	protected function get_defaults() {
		return array();
	}

	public function parse($filename) {
		ob_start();
		include(TEMPLATES_DIR . "/" . $filename);
		$c=ob_get_contents();
		ob_end_clean();
		return $c;
	}

	public function all() {
		$ret = "<ul>\n";
		foreach ($this->params as $key => $value) {
			$ret .= "<li><b>$key</b> = ".htmlspecialchars($value)."</li>";
		}
		$ret .= "</ul>\n";
		return $ret;
	}

	public function dev() {
		$ret = "<pre>";
		$ret .= "&lt;ul>\n";
		foreach ($this->params as $key => $value) {
			$ret .= "  &ltli>$key = &lt?php echo \$this->get(\"$key\") ?>&lt/li>\n";
		}
		$ret .= "&lt/ul>";
		$ret .= "</pre>";
		return $ret;
	}

	public function get($param, $filtre='row') {
		$value=$this->params[$param];
		switch($filtre) {
			case 'row':
				return $value;
			case 'esc':
				return htmlspecialchars($value);
			case 'int':
				return number_format($value, 0, ',', ' ');
			default:
				return "$filtre non trouvé";
		}
	}
	
	public function e($param, $filtre='row') {
		print($this->get($param, $filtre));
	}
	
	public function cf_options($keys = NULL) {
		global $configured_options;
		
		if ($keys == NULL) {
			$keys = array();
			foreach ($configured_options as $key) {
				if (substr($key, -5) == "_PATH") {
					$keys[] = $key;
				}
			}
		}
		
		$options = array();
		foreach ($configured_options as $key) {
			if (in_array($key, $keys)) {
				$options[strtolower($key)] = constant($key);
			}
		}
		return json_encode($options);
	}
}
