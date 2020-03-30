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

class HtmlExport extends BaseExport {

	protected function start() {
		fwrite($this->stream, "<!DOCTYPE html>\n");
		fwrite($this->stream, "<html lang=\"en-US\">\n");
		fwrite($this->stream, "<head><meta charset=\"UTF-8\"><style type=\"text/css\">");
		fwrite($this->stream, "table{border-collapse:collapse;border:none;box-sizing:border-box;margin:0;padding:0;}");
		fwrite($this->stream, "table th{color:#ffffff;margin:0;padding:.5em;background-color:#777777;border:none;white-space:nowrap;}");
		fwrite($this->stream, "table td{color:#808080;margin:0;padding:.5em;border:none;white-space:nowrap;}");
		fwrite($this->stream, "table tr:nth-child(even){background-color:#f8f8f8;}");
		fwrite($this->stream, "</style></head><body>");
		fwrite($this->stream, "<table border=\"1\">");
	}


	/**
	 * @param array $data
	 */
	public function header(array $data) {
		$row = '<tr>';
		foreach ($data as $item) {
			$row .= '<th>' . htmlspecialchars($item) . '</th>';
		}
		$row .= '</tr>';
		fwrite($this->stream, $row);
	}


	/**
	 * @param array $data
	 */
	public function add(array $data) {
		$row = '<tr>';
		foreach ($data as $item) {
			$row .= '<td>' . htmlspecialchars($item) . '</td>';
		}
		$row .= '</tr>';
		fwrite($this->stream, $row);
	}


	/**
	 *
	 */
	public function end() {
		fwrite($this->stream, "</table></html>");
		return parent::end();
	}


	/**
	 * @return string
	 */
	public function mimeType() {
		return "text/html";
	}


	/**
	 * @return string
	 */
	public function fileExtension() {
		return ".html";
	}
}
