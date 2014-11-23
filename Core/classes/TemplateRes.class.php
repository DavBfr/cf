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

class TemplateRes extends Template {
	public function __construct($params=Array()) {
		$conf = Config::getInstance();
		
		$resources = new Resources();
		
		Plugins::dispatchAll("resources", $resources);
		
		foreach($conf->get("scripts", Array()) as $script) {
			$resources->add($script);
		}
		foreach(Plugins::findAll("www/app") as $dir) {
			$resources->addDir($dir);
		}
		
		$params["scripts"] = $resources->getScripts();
		$params["stylesheets"] = $resources->getStylesheets();
		
		parent::__construct($params);
	}
}
