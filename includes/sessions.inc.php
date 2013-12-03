<?php

function is_logged() {
	return isset($_SESSION["is_logged"]) && $_SESSION["is_logged"] === true;
}


function is_logged_api() {
	return isset($_SESSION["is_logged_api"]) && $_SESSION["is_logged_api"] === true;
}


function ensure_loggedin() {
	if (!is_logged() && !is_logged_api()) {
		send_error(401);
	}
}


function ensure_loggedin_api() {
	if (!is_logged_api()) {
		send_error(401);
	}
}


function ensure_loggedin_user() {
	if (!is_logged()) {
		send_error(401);
	}
}
