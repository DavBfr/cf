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

class Csv {

	function __construct($filename = Null) {
		if ($filename !== Null) {
			header('Content-Description: File Transfer');
			header('Content-Type: application/vnd.ms-excel');
			header('Content-Disposition: attachment; filename='.$filename);
			header('Content-Transfer-Encoding: binary');
			header('Expires: 0');
			header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
			header('Pragma: public');
		}
		
		while (ob_get_length())
			ob_end_clean();
		
		$this->output = fopen('php://output', 'w');
		fwrite($this->output, "\xEF\xBB\xBF");
	}
	
	function header($data) {
		$this->add($data);
	}
	
	function add($data) {
		$buffer = fopen('php://temp', 'r+');
		fputcsv($buffer, $data, ',', '"');
		rewind($buffer);
		$csv = fgets($buffer);
		fclose($buffer);
		$csv = substr($csv, 0, -1) . "\r\n";
		fwrite($this->output, $csv);
	}

	function end() {
		fclose($this->output);
		exit();
	}

}
