<?php

abstract class Rest {
	const REQUEST_DIR = "request";


	private $routes = array();
	private $complex_routes = array();
	private $jsonpost_data = Null;


	public function __construct() {
		$this->getRoutes();
	}


	protected function addRoute($path, $method, $callback) {
		if (strpos($path, ":") !== false) {
			$vars = array();
			$path = explode("/", $path);
			foreach($path as $key => $item) {
				if (substr($item, 0, 1) == ":") {
					$vars[] = substr($item, 1);
					$path[$key] = "([^/]+)";
				} else {
					$path[$key] = str_replace(".", "\\.", $item);
				}
			}
			$path = "#^" . implode("/", $path) . "$#";
			$this->complex_routes[] = array($method, $path, $vars, $callback);
		} else {
			$this->routes[$method."@".$path] = $callback;
		}
	}


	protected abstract function getRoutes();


	protected function jsonpost() {
		if ($this->jsonpost_data === Null) {
			$this->jsonpost_data = Input::decodeJsonPost();
		}
		return $this->jsonpost_data;
	}


	public function handleRequest($method, $path) {
		if ($path == "")
			$path = "/";

		if (isset($this->routes[$method."@".$path])) {
			call_user_func(array($this, $this->routes[$method."@".$path]), array());
			ErrorHandler::error(204);
		} else {
			foreach($this->complex_routes as $route) {
				list($m, $p, $v, $c) = $route;
				if ($m == $method) {
					
					if (preg_match($p, $path, $matches) != false) {
						$pa = array();
						foreach($v as $i => $k) {
							$pa[$k] = $matches[$i+1];
						}
						call_user_func(array($this, $c), $pa);
						ErrorHandler::error(204);
					}
				}
			}
			ErrorHandler::error(404, NULL, $method."@".$path);
		}
	}


	public static function handle($method = NULL, $path = NULL) {
		if ($path == NULL) {
			$path = @$_SERVER["PATH_INFO"];
		}

		if ($method == NULL) {
			$method = $_SERVER["REQUEST_METHOD"];
		}

		while (strlen($path) > 0 && $path[0] == "/")
			$path = substr($path, 1);

		if ($path == "")
			$path = "index";

		$pos = strpos($path, "/");
		if ($pos === false) {
			$request = $path;
			$next_path = "";
		} else {
			$request = substr($path, 0, $pos);
			$next_path = substr($path, $pos);
		}

		$request = str_replace(".", "_", $request);
		
		$request_file = Plugins::find(self::REQUEST_DIR . DIRECTORY_SEPARATOR . ucwords($request) . "Rest.class.php");
		if ($request_file === NULL) {
			ErrorHandler::error(404, NULL, $request . ".php");
		}
		
		require_once($request_file);
		$class_name = ucwords($request)."Rest";
		$instance = new $class_name();
		$instance->handleRequest($method, $next_path);
		exit(0);
	}

}