<?php

abstract class Rest {

	private $routes = array();
	private $complex_routes = array();
	private $jsonpost_data = Null;


	function __construct() {
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
			$path = "#" . implode("/", $path) . "#";
			$this->complex_routes[] = array($method, $path, $vars, $callback);
		} else {
			$this->routes[$method."@".$path] = $callback;
		}
	}


	protected abstract function getRoutes();


	protected function jsonpost() {
		if ($this->jsonpost_data === Null) {
			$this->jsonpost_data = decode_json_post();
		}
		return $this->jsonpost_data;
	}


	public function handle_request($method, $path) {
		if ($path == "")
			$path = "/";

		if (isset($this->routes[$method."@".$path])) {
			call_user_func(array($this, $this->routes[$method."@".$path]), array());
			send_error(204);
		} else {
			foreach($this->complex_routes as $route) {
				list($m, $p, $v, $c) = $route;
				if ($m == $method) {
					if (preg_match($p, $path, $matches) !== false) {
						$p = array();
						foreach($v as $i => $k) {
							$p[$k] = $matches[$i+1];
						}
						call_user_func(array($this, $c), $p);
						send_error(204);
					}
				}
			}
			send_error(404, NULL, $method."@".$path);
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

		$request_file = REQUEST_DIR . "/" . $request . ".php";
		if (file_exists($request_file)) {
			require_once($request_file);
			$class_name = ucwords($request);
			$instance = new $class_name();
			$instance->handle_request($method, $next_path);
			exit(0);
		}

		send_error(404, NULL, REQUEST_DIR . "/" . $request . ".php");
	}

}
