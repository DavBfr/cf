<?php

define("CF_DIR", "@CF_DIR@");
define("ROOT_DIR", dirname(__file__));
require(CF_DIR . "/cf.php");
Output::redirect(WWW_PATH);
