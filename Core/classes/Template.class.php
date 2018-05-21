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
	private $caching = true;


	/**
	 * Template constructor.
	 * @param array $params
	 */
	public function __construct($params = array()) {
		$this->params = array_merge($this->get_defaults(), $params);
	}


	/**
	 * @return array
	 */
	protected function get_defaults() {
		return array();
	}


	/**
	 * @param string $key
	 * @param mixed $value
	 */
	public function set($key, $value) {
		$this->params[$key] = $value;
	}


	/**
	 * @param string $filename
	 * @return bool|string
	 */
	public static function findTemplate($filename) {
		if ($filename[0] != DIRECTORY_SEPARATOR) {
			$template_file = Plugins::find(self::TEMPLATES_DIR . DIRECTORY_SEPARATOR . $filename);
			if ($template_file !== null) {
				return $template_file;
			}
			return false;
		}
		return file_exists($filename);
	}


	/**
	 * @param string $filenames
	 * @return string
	 * @throws \Exception
	 */
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
			ErrorHandler::error(404, null, implode(", ", $filenames));

		ob_start();
		include($template);
		$content = ob_get_contents();
		ob_end_clean();
		return $this->squareStache($content);
	}


	/**
	 * @param string $filename
	 * @param bool $optional
	 * @throws \Exception
	 */
	public function insert($filename, $optional = false) {
		$template = self::findTemplate($filename);
		if ($template === false) {
			if ($optional) {
				return;
			}
			ErrorHandler::error(404, null, $filename);
		}

		include($template);
	}


	/**
	 * @param string $filenames
	 * @param array $params
	 * @param string $class
	 * @throws \Exception
	 */
	public function insertNew($filenames, $params = null, $class = "Template") {
		$nsclass = __NAMESPACE__ . "\\" . $class;
		if ($params === null)
			$params = array();
		/** @var Template $tpt */
		$tpt = new $nsclass(array_merge($this->params, $params));
		echo $tpt->parse($filenames);
	}


	/**
	 * @param string $filename
	 * @param string $contentType
	 * @param string $encoding
	 * @throws \Exception
	 */
	public function output($filename, $contentType = "text/html", $encoding = "utf-8") {
		while (ob_get_length())
			ob_end_clean();

		header("Content-Type: ${contentType};charset=${encoding}");
		echo $this->parse($filename);
		Output::finish();
	}


	/**
	 *
	 */
	public function disable_cache() {
		$this->caching = false;
	}


	/**
	 * @param string $filename
	 * @param string $contentType
	 * @param string $encoding
	 * @throws \Exception
	 */
	public function outputCached($filename, $contentType = "text/html", $encoding = "utf-8") {
		while (ob_get_length())
			ob_end_clean();

		header("Content-Type: ${contentType};charset=${encoding}");

		if (Session::Has(Session::rights_key)) {
			$rights = Session::Get(Session::rights_key);
		} else {
			$rights = 0;
		}

		$cache = Cache::Priv(sha1(json_encode(array(
			$filename,
			Lang::getLang(),
			$rights,
			$this->params
		))), ".html");
		$cache->outputIfCached();

		$content = $this->parse($filename);
		$contentMin = Plugins::dispatch("minify_html", $content);
		if ($contentMin !== null)
			$content = $contentMin;

		if ($this->caching)
			$cache->setContents($content);

		echo $content;
		Output::finish();
	}


	/**
	 * @param string $filename
	 * @param string $default
	 * @return string
	 * @throws \Exception
	 */
	public function media($filename, $default = null) {
		$file = Resources::find($filename);
		if ($file !== null) {
			return Resources::web($file);
		}
		return $default;
	}


	/**
	 * @return string
	 */
	public function all() {
		$ret = "<ul>\n";
		foreach ($this->params as $key => $value) {
			$ret .= "<li><b>$key</b> = " . htmlspecialchars($value) . "</li>";
		}
		$ret .= "</ul>\n";
		return $ret;
	}


	/**
	 * @return string
	 */
	public function dev() {
		$ret = "<pre>";
		$ret .= "&lt;ul>\n";
		foreach ($this->params as $key => $value) {
			$ret .= "  &ltli>$key = [[ $key ]]&lt/li>\n";
		}
		$ret .= "&lt/ul>";
		$ret .= "</pre>";
		return $ret;
	}


	/**
	 * @param mixed $value
	 * @param string $filter
	 * @return string
	 * @throws \Exception
	 */
	protected function filter($value, $filter) {
		switch ($filter) {
			case 'raw':
				return $value;
			case 'tr':
				return Lang::get($value);
			case 'esc':
				return htmlspecialchars($value);
			case 'json':
				return json_encode($value);
			case 'st':
				return strip_tags($value);
			case 'ucwords':
				return ucwords($value);
			case 'ucfirst':
				return ucfirst($value);
			case 'uc':
				return strtoupper($value);
			case 'lc':
				return strtolower($value);
			case 'int':
				return number_format($value, 0, ',', ' ');
			default:
				return "${filter} not found";
		}
	}


	/**
	 * @param string $param
	 * @return bool
	 */
	public function has($param) {
		return array_key_exists($param, $this->params);
	}


	/**
	 * @param string $param
	 * @param string $filter
	 * @param mixed $default
	 * @return string
	 * @throws \Exception
	 */
	public function get($param, $filter = 'raw', $default = null) {
		if (array_key_exists($param, $this->params)) {
			$value = $this->params[$param];
		} else {
			if ($default === null)
				$value = $param;
			else
				$value = $default;
		}
		return $this->filter($value, $filter);
	}


	/**
	 * @param string $key
	 * @param string $filter
	 * @param mixed $default
	 * @return string
	 * @throws \Exception
	 */
	public function config($key, $filter = 'raw', $default = null) {
		$config = Config::getInstance();
		$value = $config->get($key, $default);
		return $this->filter($value, $filter);
	}


	/**
	 * @param string $param
	 * @param string $filtre
	 * @param mixed $default
	 * @throws \Exception
	 */
	public function out($param, $filtre = 'raw', $default = null) {
		echo $this->get($param, $filtre, $default);
	}


	/**
	 * @param string $param
	 * @throws \Exception
	 */
	public function tr($param) {
		echo $this->get($param, 'tr');
	}


	/**
	 * @param array $keys
	 * @throws \Exception
	 */
	public function cf_options($keys = null) {
		if ($keys == null) {
			$keys = array();
			foreach (Options::getAll() as $key => $val) {
				if (substr($key, -5) == "_PATH") {
					$keys[] = $key;
				}
			}
		}

		$options = array();
		foreach (Options::getAll() as $key => $val) {
			if (in_array($key, $keys)) {
				$options[strtolower($key)] = $val;
			}
		}

		if (Session::Has(Session::rights_key)) {
			$options["rights"] = Session::Get(Session::rights_key);
		} else {
			$options["rights"] = array();
		}

		if (Session::Has(AbstractLogin::userid)) {
			$options["user"] = Session::Get(AbstractLogin::userid);
		} else {
			$options["user"] = false;
		}

		foreach (Plugins::dispatchAll("cf_options") as $opt) {
			$options = array_merge($options, $opt);
		}

		if (DEBUG) {
			$options['debug'] = DEBUG;
		}

		$this->disable_cache();

		echo json_encode($options);
	}


	/**
	 * @param $input
	 * @return mixed
	 * @throws \ReflectionException
	 * @throws \Exception
	 */
	protected function squareStache($input) {
		$re = '/\[\[ *([\w\d_\-\.\/]+) *(\| *([\w\d_]*) *)?]]/';

		if (preg_match_all($re, $input, $matches, PREG_SET_ORDER | PREG_OFFSET_CAPTURE)) {
			foreach (array_reverse($matches) as $match) {
				$key = $match[1][0];
				if (count($match) >= 3)
					$filter = $match[3][0];
				else
					$filter = 'raw';

				switch ($filter) {
					case 'media':
						$val = $this->media($key);
						break;
					case 'tr':
						$val = $this->get($key, $filter);
						break;
					case 'date':
						$val = date(Lang::get('core.date', $key === 'now' ? time() : $this->get($key)));
						break;
					case 'time':
						$val = date(Lang::get('core.time', $key === 'now' ? time() : $this->get($key)));
						break;
					case 'datetime':
						$val = date(Lang::get('core.datetime', $key === 'now' ? time() : $this->get($key)));
						break;
					case 'insert':
						if (substr($key, strrpos($key, ".")) == '.php')
							$nsclass = __CLASS__;
						else
							$nsclass = (new \ReflectionClass($this))->getName();
						/** @var Template $tpt */
						$tpt = new $nsclass($this->params);
						$val = $tpt->parse($key);
						break;
					default:
						if ($this->has($key))
							$val = $this->get($key, $filter);
						else
							$val = $this->config($key, $filter);
				}

				$input = substr_replace($input, $val, $match[0][1], strlen($match[0][0]));

			}
		}

		return $input;
	}


}
