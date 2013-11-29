<?php

function is_logged() {
	return isset($_SESSION["is_logged"]) && $_SESSION["is_logged"] === true;
}

function ensure_loggedin() {
	if (!is_logged()) {
		send_error(401);
	}
}
