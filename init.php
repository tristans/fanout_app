<?php

require "autoload.php";
Predis\Autoloader::register();

require "lib/Slim/Slim.php";
\Slim\Slim::registerAutoloader();

CONST NOT_YET_STORED = "not_yet_stored";

// models
require_once "User.php";
require_once "Activity.php";
