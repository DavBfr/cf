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

class Bdd {
	private static $instance = NULL;

	private $helper;
	private $driver;


	private function __construct() {
		$this->driver = substr(DBNAME, 0, strpos(DBNAME, ":"));
		$helper = ucFirst($this->driver)."Helper";
		if (class_exists($helper, true)) {
			$this->helper = new $helper(DBNAME, DBLOGIN, DBPASSWORD);
		} else {
			$this->helper = new PDOHelper(DBNAME, DBLOGIN, DBPASSWORD);
		}
	}


	public static function getInstance() {
		if (is_null(self::$instance)) {
			self::$instance = new self();
		}

		return self::$instance;
	}


	public function __call($name, $arguments) {
		return call_user_func_array(array($this->helper, $name), $arguments);
	}

}
