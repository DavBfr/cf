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

class GraphViz {
	
	static public function DBSchema() {
		$config = Config::getInstance();

		$dot = "digraph {\ngraph [pad=\"0.5\", nodesep=\"0.5\", ranksep=\"2\"];\nnode [shape=plain]\nrankdir=LR;\n";
		$links = "";

		foreach($config->get("model", array()) as $name => $model) {
			$dm = "$name [label=<\n<table border=\"0\" cellborder=\"1\" cellspacing=\"0\">\n<tr><td bgcolor=\"#91cdd1\"><b>$name</b></td></tr>\n";
			$mn = __NAMESPACE__ . "\\" . ucfirst($name)."Model";
			/** @var Model $mc */
			$mc = new $mn();
			foreach($mc->getFields() as $field) {
				$fn = $field->getName();

				$type = $field->getType();
				if ($field->isPrimary()) {
					$dm .= "<tr><td align=\"left\" bgcolor=\"#9bd7c5\" port=\"$fn\"><b>$fn</b> <i>$type</i></td></tr>\n";
				}else {
					$dm .= "<tr><td align=\"left\" bgcolor=\"#a9e7eb\" port=\"$fn\">$fn <i>$type</i></td></tr>\n";
				}

				if ($field->isForeign()) {
					$foreign = $field->getForeign();
					if (is_array($foreign)) {
						$links .= "$name:$fn -> {$foreign[0]}:{$foreign[1]};\n";
					}
				}
			}

			$dm .= "</table>>];\n";
			$dot .= $dm;
		}

		$dot .= $links . "}\n";

		return $dot;
	}
}
