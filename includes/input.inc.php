<?php

function decode_json_post() {
	return json_decode(file_get_contents("php://input"), true);
}
