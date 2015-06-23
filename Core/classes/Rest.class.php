<?php
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

abstract class Rest {
	const REQUEST_DIR = "request";


	private $routes = array();
	private $complex_routes = array();
	private $jsonpost_data = Null;
	
	protected $path = Null;
	protected $method = Null;
	protected $mp = Null;


	public function __construct() {
		$this->getRoutes();
	}


	protected function addRoute($path, $method, $callback) {
		if (strpos($path, ":") !== false) {
			$vars = array();
			$aPath = explode("/", $path);
			foreach($aPath as $key => $item) {
				if (substr($item, 0, 1) == ":") {
					$vars[] = substr($item, 1);
					$aPath[$key] = "([^/]+)";
				} else {
					$aPath[$key] = str_replace(".", "\\.", $item);
				}
			}
			$rPath = "#^" . implode("/", $aPath) . "$#";
			$this->complex_routes[$method."@".$path] = array($method, $rPath, $vars, $callback);
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


	protected function preCheck($mp) {
		return true;
	}


	protected function preProcess($r) {
	}


	protected function processNotFound($method) {
		ErrorHandler::error(404, NULL, $method);
	}


	public function handleRequest($method, $path) {
		if ($path == "")
			$path = "/";
		
		$this->method = $method;
		$this->path = $path;
		$this->mp = $method."@".$path;

		if (isset($this->routes[$this->mp])) {
			if (!$this->preCheck($this->mp)) {
				ErrorHandler::error(401);
			}
			$this->preProcess(array());
			call_user_func(array($this, $this->routes[$this->mp]), array());
			ErrorHandler::error(204);
		} else {
			foreach($this->complex_routes as $cPath=>$route) {
				list($m, $p, $v, $c) = $route;
				if ($m == $method) {
					
					if (preg_match($p, $path, $matches) != false) {
						$pa = array();
						foreach($v as $i => $k) {
							$pa[$k] = $matches[$i+1];
						}
						$this->mp = $cPath;
						if (!$this->preCheck($this->mp)) {
							ErrorHandler::error(401);
						}
						$this->preProcess($pa);
						call_user_func(array($this, $c), $pa);
						ErrorHandler::error(204);
					}
				}
			}
			$this->processNotFound($this->mp);
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
			ErrorHandler::error(404, NULL, ucwords($request) . "Rest.class.php");
		}
		
		require_once($request_file);
		$class_name = ucwords($request)."Rest";
		$instance = new $class_name();
		$instance->handleRequest($method, $next_path);
		exit(0);
	}

}
