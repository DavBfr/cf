<?php
/**
 * Copyright (C) 2013-2014 David PHAM-VAN
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License
 * as published by the Free Software Foundation; version 2
 * of the License.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301, USA.
 **/

class ErrorRest extends Rest {

	public function getRoutes() {
		$this->addRoute("/", "GET", "error");
		$this->addRoute("/:code", "GET", "error");
	}
	
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
			"code"=>$code,
			"message"=>$message,
			"body"=>(DEBUG?CorePlugin::info():""),
			"baseline"=>CorePlugin::getBaseline(),
		));
		$tpt->output(ERROR_TEMPLATE);
	}

}
