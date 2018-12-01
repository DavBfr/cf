<?php namespace DavBfr\CF;
/**
 * Copyright (C) 2013-2018 David PHAM-VAN
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

abstract class RestApi extends Rest {

	/**
	 * @param string $method
	 * @param string $path
	 * @throws \Exception
	 */
	public function handleRequest($method, $path) {
		header('Access-Control-Allow-Origin: *');
		header("Access-Control-Allow-Headers: Origin, X-Requested-With, Content-Type, Accept, Cf-Token");
		header("Access-Control-Allow-Methods: OPTIONS, GET, POST, DELETE, PUT, PATCH");

		if ($method == "OPTIONS") {
			Output::finish();
		}

		parent::handleRequest($method, $path);
	}


	/**
	 * @param string $mp
	 * @return bool
	 * @throws \Exception
	 */
	protected function preCheck($mp) {
		DEBUG || Session::ensureLoggedinApi();
		return parent::preCheck($mp);
	}
}
