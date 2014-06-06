<?php

class Template {
	const TEMPLATES_DIR = "templates";

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


	public static function findTemplate($filename) {
		if ($filename[0] != DIRECTORY_SEPARATOR) {
			$template_file = Plugins::find(self::TEMPLATES_DIR . DIRECTORY_SEPARATOR . $filename);
			if ($template_file !== NULL) {
				return $template_file;
			}
			return False;
		}
		return file_exists($filename);
	}


	public function parse($filename) {
		$template = self::findTemplate($filename);
		if ($template === False)
			ErrorHandler::error(404, NULL, $filename);

		ob_start();
		include($template);
		$content=ob_get_contents();
		ob_end_clean();
		return $content;
	}


	public function insert($filename, $optional = false) {
		$template = self::findTemplate($filename);
		if ($template === False) {
			if ($optional) {
				return;
			}
			ErrorHandler::error(404, NULL, $filename);
		}

		include($template);
	}


	public function output($filename, $contentType="text/html", $encoding="utf-8") {
		ob_end_clean();
		header("Content-Type: ${contentType};charset=${encoding}");
		echo $this->parse($filename);
		die();
	}


	public function media($filename) {
		$file = Resources::find($filename);
		if ($file !== NULL) {
			return Resources::web($file);
		}
		return NULL;
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
			case 'tr':
				return Lang::get($value);
			case 'esc':
				return htmlspecialchars($value);
			case 'int':
				return number_format($value, 0, ',', ' ');
			default:
				return "${filter} not found";
		}
	}


	public function has($param) {
		return array_key_exists($param, $this->params);
	}


	public function get($param, $filter='row') {
		if (array_key_exists($param, $this->params)) {
			$value = $this->params[$param];
		} else {
			$value = $param;
		}
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


	public function tr($param) {
		print(Lang::get($param));
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
		
		if (Session::Has(Session::rights_key)) {
			$options["rights"] = Session::Get(Session::rights_key);
		} else {
			$options["rights"] = Array();
		}
		
		print(json_encode($options));
	}

}
