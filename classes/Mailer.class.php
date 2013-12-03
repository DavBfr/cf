<?php

class Mailer extends PHPMailer {

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
