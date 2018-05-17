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

class Mailer extends \PHPMailer {

	/**
	 * Mailer constructor.
	 * @throws \phpmailerException
	 */
	public function __construct() {
		parent::__construct(false);
		$this->IsSMTP();
		$this->SMTPAuth = true;
		$this->SMTPSecure = "ssl";
		$this->Host = "smtp.gmail.com";
		$this->Port = 465;
		$this->Username   = GMAIL_LOGIN;
		$this->Password   = GMAIL_PASS;
		$this->AuthType = 'PLAIN';
		$this->CharSet = 'utf-8';
		$this->SetFrom(GMAIL_LOGIN);
	}
}
