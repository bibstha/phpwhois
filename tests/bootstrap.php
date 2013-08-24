<?php
date_default_timezone_set('Europe/Berlin');
$incPath = get_include_path();
set_include_path(
   get_include_path() . ":" .
   realpath(dirname(__FILE__) . "/../lib/phpwhois") . ":" .
   realpath(dirname(__FILE__) . "/../lib")
);
define('TEST_ROOT', dirname(__FILE__));