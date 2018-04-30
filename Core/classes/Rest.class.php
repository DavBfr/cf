<?php namespace DavBfr\CF;
/**
 * Copyright (C) 2013-2016 David PHAM-VAN
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

abstract class Rest {
	const REQUEST_DIR = "request";
	const REQUEST_PREFIX = "|^v\d+/|";

	private $list = array();
	private $routes = array();
	private $complex_routes = array();
	private $jsonpost_data = null;

	protected $path = null;
	protected $method = null;
	protected $mp = null;


	/**
	 * Rest constructor.
	 */
	public function __construct() {
		$this->getRoutes();
	}


	/**
	 * @param string $path
	 * @param string $method
	 * @param callable $callback
	 * @param array $openapi
	 */
	protected function addRoute($path, $method, $callback, $openapi = array()) {
		$this->list[] = array($path, $method, $callback, $openapi);

		if (strpos($path, ":") !== false) {
			$vars = array();
			$aPath = explode("/", $path);
			foreach ($aPath as $key => $item) {
				if (substr($item, 0, 1) == ":") {
					$vars[] = substr($item, 1);
					$aPath[$key] = "([^/]+)";
				} else {
					$aPath[$key] = str_replace(".", "\\.", $item);
				}
			}
			$rPath = "#^" . implode("/", $aPath) . "$#";
			$this->complex_routes[$method . "@" . $path] = array($method, $rPath, $vars, $callback);
		} else {
			$this->routes[$method . "@" . $path] = $callback;
		}
	}


	/**
	 *
	 */
	abstract protected function getRoutes();


	/**
	 * @return array
	 * @throws \Exception
	 */
	protected function jsonpost() {
		if ($this->jsonpost_data === null) {
			$this->jsonpost_data = Input::decodeJsonPost();
		}
		return $this->jsonpost_data;
	}


	/**
	 * @param string $mp
	 * @return bool
	 */
	protected function preCheck($mp) {
		return true;
	}


	/**
	 * @param array $r
	 */
	protected function preProcess($r) {
	}


	/**
	 * @param string $method
	 * @throws \Exception
	 */
	protected function processNotFound($method) {
		ErrorHandler::error(404, null, get_class($this) . "::" . $method);
	}


	/**
	 * @param string $method
	 * @param string $path
	 * @throws \Exception
	 */
	public function handleRequest($method, $path) {
		if ($path == "")
			$path = "/";

		$this->method = $method;
		$this->path = $path;
		$this->mp = $method . "@" . $path;

		if (isset($this->routes[$this->mp])) {
			restore_exception_handler();
			try {
				if (!$this->preCheck($this->mp)) {
					ErrorHandler::error(401);
				}
				$this->preProcess(array());
				call_user_func(array($this, $this->routes[$this->mp]), array());
			} catch (\Exception $e) {
				ErrorHandler::getInstance()->exceptionHandler($e);
			}
			ErrorHandler::error(204);
		} else {
			foreach ($this->complex_routes as $cPath => $route) {
				list($m, $p, $v, $c) = $route;
				if ($m == $method) {

					if (preg_match($p, $path, $matches) != false) {
						$pa = array();
						foreach ($v as $i => $k) {
							$pa[$k] = $matches[$i + 1];
						}
						$this->mp = $cPath;
						restore_exception_handler();
						try {
							if (!$this->preCheck($this->mp)) {
								ErrorHandler::error(401);
							}
							$this->preProcess($pa);
							call_user_func(array($this, $c), $pa);
						} catch (\Exception $e) {
							ErrorHandler::getInstance()->exceptionHandler($e);
						}
						ErrorHandler::error(204);
					}
				}
			}
			$this->processNotFound($this->mp);
		}
	}


	/**
	 * @param string $method
	 * @param string $path
	 * @throws \Exception
	 */
	public static function handle($method = null, $path = null) {
		if ($path == null) {
			$path = @$_SERVER["PATH_INFO"];
		}

		if ($method == null) {
			$method = $_SERVER["REQUEST_METHOD"];
		}

		while (strlen($path) > 0 && $path[0] == "/")
			$path = substr($path, 1);

		if ($path == "")
			$path = "index";

		if (preg_match(self::REQUEST_PREFIX, $path, $matches) != false) {
			$prefix = $matches[0];
			$path = substr($path, strlen($prefix));
		} else {
			$prefix = "";
		}

		$pos = strpos($path, "/");
		if ($pos === false) {
			$request = $path;
			$next_path = "";
		} else {
			$request = substr($path, 0, $pos);
			$next_path = substr($path, $pos);
		}

		$request = str_replace(".", "_", $request);
		$request = str_replace("-", " ", $request);
		$request = ucwords($request);
		$request = str_replace(" ", "", $request) . "Rest";

		$request_file = Plugins::find(self::REQUEST_DIR . DIRECTORY_SEPARATOR . $prefix . $request . ".class.php");
		if ($request_file === null) {
			ErrorHandler::error(404, null, $prefix . $request . ".class.php");
		}

		require_once($request_file);
		$class_name = __NAMESPACE__ . "\\" . $request;
		/** @var Rest $instance */
		$instance = new $class_name();
		$instance->handleRequest($method, $next_path);
		ErrorHandler::error(204);
	}


	/**
	 * @return array
	 * @throws \ReflectionException
	 */
	public function buildApi() {
		$classname = explode('\\', get_class($this));
		$tag = substr(array_pop($classname), 0, -4);
		// Todo: Manage special cases
		// $request = str_replace(".", "_", $request);
		// $request = str_replace("-", " ", $request);
		// $request = ucwords($request);
		// $request = str_replace(" ", "", $request) . "Rest";
		$prefix = "/" . strtolower($tag);

		$class = new \ReflectionClass($this);
		$path = basename(dirname($class->getFileName()));
		if (preg_match("|v\d+|", $path, $matches) != false) {
			$prefix = "/" . $path . $prefix;
		}

		$paths = array();
		foreach ($this->list as $route) {
			list($path, $method, $callback, $openapi) = $route;
			$path = $prefix . $path;
			$vars = array();

			if (strpos($path, ":") !== false) {
				$aPath = explode("/", $path);
				foreach ($aPath as $key => $item) {
					if (substr($item, 0, 1) == ":") {
						$item = substr($item, 1);
						$vars[] = array(
							"name" => $item,
							"in" => "path",
							"required" => true,
							"description" => $item,
							"schema" => array("type" => "string"),
						);
						$aPath[$key] = "{" . $item . "}";
					}
				}
				$path = implode("/", $aPath);
			}

			if (!array_key_exists($path, $paths))
				$paths[$path] = array();

			$paths[$path][strtolower($method)] = array_merge(array(
				"summary" => $callback,
				"tags" => array($tag),
				"operationId" => $callback,
				"parameters" => $vars,
				"responses" => array(
					200 => array("description" => "OK"),
					400 => array("description" => ErrorHandler::$messagecode[400]),
					401 => array("description" => ErrorHandler::$messagecode[401]),
					417 => array("description" => ErrorHandler::$messagecode[417]),
					500 => array("description" => ErrorHandler::$messagecode[500]),
				),
			), $openapi);
		}

		return $paths;
	}


	/**
	 * @return array
	 * @throws \ReflectionException
	 */
	public static function getOpenApi() {
		$config = Config::getInstance();
		$authors = $config->get("composer.authors");

		$api = array(
			"openapi" => "3.0.0",
			"info" => array(
				"version" => $config->get("composer.version"),
				"title" => $config->get("title"),
				"description" => $config->get("description"),
				"contact" => array(
					"name" => $authors[0]["name"],
					"email" => $authors[0]["email"],
				),
			),
			"license" => array(
				"name" => $config->get("composer.license"),
			),
			"servers" => array(
				array("url" => "http" . ($_SERVER["HTTPS"] ? "s" : "") . "://" . $_SERVER['HTTP_HOST'] . REST_PATH),
			),
		);

		$paths = array();
		foreach (Plugins::get_plugins() as $plugin) {
			$request = Plugins::get($plugin)->getDir() . DIRECTORY_SEPARATOR . Rest::REQUEST_DIR;
			$files = System::globRec($request . DIRECTORY_SEPARATOR . "*Rest.class.php");
			foreach ($files as $file) {
				$cn = __NAMESPACE__ . "\\" . substr(basename($file), 0, -10);
				try {
					if (!class_exists($cn, false)) {
						require_once($file);
					}
					/** @var Rest $req */
					$req = new $cn();
				} catch (\Exception $e) {
					continue;
				}

				$paths = array_merge($paths, $req->buildApi());
			}
		}

		$api["paths"] = $paths;

		return $api;
	}
}
