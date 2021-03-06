<?php namespace DavBfr\CF;
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

class MdTemplate extends Template {

	public function parse($filenames) {
		if (!is_array($filenames)) {
			$filenames = array($filenames);
		}

		$template = false;
		foreach ($filenames as $filename) {
			$template = self::findTemplate($filename);
			if ($template !== false)
				break;
		}
		if ($template === false)
			ErrorHandler::error(404, null, implode(", ", $filenames));

		$Parsedown = new Parsedown();
		return $Parsedown->text($this->squareStache(file_get_contents($template)));
	}

}
