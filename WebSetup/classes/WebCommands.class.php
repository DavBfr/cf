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

class WebCommands extends Cli {
	private $output;

	public function __construct($args) {
		$this->output = "";
		parent::__construct($args);
		$logger = Logger::getInstance();
		$logger->setLevel(Logger::DEBUG);
	}


	public function output($color, $s) {
		switch ($color) {
			case self::ansierr:
				$st = "<span class=\"ansierr\">";
				$et = "</span>";
				break;
			case self::ansiinfo:
				$st = "<span class=\"ansiinfo\">";
				$et = "</span>";
				break;
			case self::ansidebug:
				$st = "<span class=\"ansidebug\">";
				$et = "</span>";
				break;
			case self::ansilog:
				$st = "<span class=\"ansilog\">";
				$et = "</span>";
				break;
			case self::ansiwarn:
				$st = "<span class=\"ansiwarn\">";
				$et = "</span>";
				break;
			case self::ansilerr:
				$st = "<span class=\"ansilerr\">";
				$et = "</span>";
				break;
			case self::ansicrit:
				$st = "<span class=\"ansicrit\">";
				$et = "</span>";
				break;
			default:
				$st = "";
				$et = "";
		}
		$this->output .= $st . htmlspecialchars($s) . $et;
	}


	public function getCommands() {
		return $this->commands;
	}


	public function getOutput() {
		return $this->output;
	}

}
