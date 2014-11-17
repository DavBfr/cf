<?php

class Template {
	private $params;


	public function __construct($params=Array()) {
		$this->params=array_merge($this->get_defaults(), $params);
	}


	protected function get_defaults() {
		return array();
	}


	public function set($key, $value) {
		$this->params[$key] = $value;
	}


	public function parse($filename) {
		ob_start();
		include(TEMPLATES_DIR . "/" . $filename);
		$c=ob_get_contents();
		ob_end_clean();
		return $c;
	}


	public function output($filename) {
		echo $this->parse($filename);
		die();
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
	
	
	protected function filter($value, $filter) {
		switch($filter) {
			case 'row':
				return $value;
			case 'esc':
				return htmlspecialchars($value);
			case 'int':
				return number_format($value, 0, ',', ' ');
			default:
				return "${filter} not found";
		}
	}


	public function get($param, $filter='row') {
		$value = $this->params[$param];
		return $this->filter($value, $filter);
	}


	public function config($key, $filter='row') {
		$config = Config::getInstance();
		$value = $config->get($key, $key);
		return $this->filter($value, $filter);
	}


	public function out($param, $filtre='row') {
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
		print(json_encode($options));
	}

}
