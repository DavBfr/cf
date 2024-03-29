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

class ErrorRest extends Rest {

	/**
	 *
	 */
	public function getRoutes() {
		$this->addRoute("/", "GET", "error");
		$this->addRoute("/:code", "GET", "error");
	}


	/**
	 * @param array $r
	 * @throws \Exception
	 */
	protected function error($r) {
		if (array_key_exists("code", $r))
			$code = $r["code"];
		else
			$code = $_SERVER["REDIRECT_STATUS"];

		if (array_key_exists($code, ErrorHandler::$messagecode))
			$message = ErrorHandler::$messagecode[$code];
		else
			$message = "";

		$tpt = new TemplateRes(array(
			"code" => $code,
			"message" => $message,
			"body" => (Options::get('DEBUG') ? CorePlugin::info() : ""),
			"baseline" => CorePlugin::getBaseline(),
		));
		$tpt->output(Options::get('ERROR_TEMPLATE'));
	}

}
