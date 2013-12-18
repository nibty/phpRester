<?php

date_default_timezone_set('America/Los_Angeles');

//  Logs
define("LOG_OPTIONS", "DEBUG,INFO,WARNING,ERROR");
define("LOG_FILE", "/var/log/httpd/rest_api.log");
define("REQUEST_LOG_FILE", "/var/log/httpd/rest_api.requests.log");
define("LOG_PREFIX", "");
define("DEBUG", "DEBUG");
define("INFO", "INFO");
define("WARNING", "WARNING");
define("ERROR", "ERROR");

// Param data types
define("STRING", "string");
define("INTEGER", "integer");
define("FLOAT", "float");
define("BOOLEAN", "boolean");
define("BOOL", "boolean");
define("STRING_ARRAY", "string_array");
define("INTEGER_ARRAY", "integer_array");
define("FLOAT_ARRAY", "float_array");
define("EMAIL", "FILTER_SANITIZE_EMAIL");

// Beanstalk
define("BEANSTALK_SERVER", "mtju1");
define("BEANSTALK_PORT", "11300");
define("QUEUE_TUBE", "tube");