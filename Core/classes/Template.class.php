<?php namespace DavBfr\CF;
/**
 * Copyright (C) 2013-2015 David PHAM-VAN
 *
 * This library is free software; you can redistribute it and/or
 * modify it under the terms of the GNU Lesser General Public
 * License as published by the Free Software Foundation; either
 * version 2.1 of the License, or (at your option) any later version.
 *
 * This library is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the GNU
 * Lesser General Public License for more details.
 *
 * You should have received a copy of the GNU Lesser General Public
 * License along with this library; if not, write to the Free Software
 * Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301, USA.
 **/

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
			return false;
		}
		return file_exists($filename);
	}


	public function parse($filenames) {
		if (!is_array($filenames)) {
			$filenames = array($filenames);
		}

		$template = false;
		foreach ($filenames as $filename) {
			$template = self::findTemplate($filename);
			if ($template !== false)
			break;
		}
		if ($template === false)
			ErrorHandler::error(404, NULL, implode(", ", $filenames));

		ob_start();
		include($template);
		$content=ob_get_contents();
		ob_end_clean();
		return $content;
	}


	public function insert($filename, $optional = false) {
		$template = self::findTemplate($filename);
		if ($template === false) {
			if ($optional) {
				return;
			}
			ErrorHandler::error(404, NULL, $filename);
		}

		include($template);
	}


	public function insertNew($filenames, $params = NULL) {
		$tpt = new Template(array_merge($this->params, $params));
		echo $tpt->parse($filenames);
	}


	public function output($filename, $contentType="text/html", $encoding="utf-8") {
		while (ob_get_length())
			ob_end_clean();

		header("Content-Type: ${contentType};charset=${encoding}");
		print($this->parse($filename));
		Output::finish();
	}


	public function outputCached($filename, $contentType="text/html", $encoding="utf-8") {
		if (DEBUG)
			$this->output($filename, $contentType, $encoding);

		while (ob_get_length())
			ob_end_clean();

		header("Content-Type: ${contentType};charset=${encoding}");

		$cache = Cache::Priv(sha1(json_encode(array(
			$filename,
			Lang::getLang(),
			$this->params
		))), ".html");
		if ($cache->exists()) {
			print($cache->getContents());
			Output::finish();
		}

		$content = $this->parse($filename);

		$cache->setContents($content);
		print($content);
		Output::finish();
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
			case 'raw':
				return $value;
			case 'tr':
				return Lang::get($value);
			case 'esc':
				return htmlspecialchars($value);
			case 'st':
				return strip_tags($value);
			case 'int':
				return number_format($value, 0, ',', ' ');
			default:
				return "${filter} not found";
		}
	}


	public function has($param) {
		return array_key_exists($param, $this->params);
	}


	public function get($param, $filter='raw', $default = NULL) {
		if (array_key_exists($param, $this->params)) {
			$value = $this->params[$param];
		} else {
			if ($default === NULL)
				$value = $param;
			else
				$value = $default;
		}
		return $this->filter($value, $filter);
	}


	public function config($key, $filter='raw', $default = NULL) {
		$config = Config::getInstance();
		$value = $config->get($key, $key, $default);
		return $this->filter($value, $filter);
	}


	public function out($param, $filtre='raw', $default = NULL) {
		print($this->get($param, $filtre, $default));
	}


	public function tr($param) {
		print($this->get($param, 'tr'));
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

		foreach (Plugins::dispatchAll("cf_options") as $opt) {
			$options = array_merge($options, $opt);
		}

		print(json_encode($options));
	}

}
