<?php
/**
 * Copyright (C) 2013 David PHAM-VAN
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

class SkelPlugin extends Plugins {

	public function cli($cli) {
		$cli->addCommand("skel:init", array($this, "skel"), "Initialize a new CF project");
	}

	public function skel() {
		Cli::copyTree($this->getDir(), getcwd());
		unlink(getcwd() . DIRECTORY_SEPARATOR . basename(__file__));
		$index = file_get_contents(getcwd() . DIRECTORY_SEPARATOR . "index.php");
		$index = str_replace("@CF_DIR@", CF_DIR, $index);
		file_put_contents(getcwd() . DIRECTORY_SEPARATOR . "index.php", $index);
		System::ensureDir(DATA_DIR);
	}

}
