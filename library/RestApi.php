<?php

error_reporting(E_ALL ^ E_NOTICE);
require_once(dirname(__FILE__) . "/../config.inc");

function outputToUser($result)
{
    global $request;
    global $startTime;
    global $error;

    // Render to client
    $viewName = ucfirst($request->format) . 'View';
    if (class_exists($viewName)) {
        $processTime = round((microtime(TRUE) - $startTime) * 1000);
        logIt("API Request", "INFO", REQUEST_LOG_FILE);
        $view = new $viewName($result, $processTime, $request->version, $error, TRUE,
            $request->parameters['pretty'], $request->parameters['textarea']);
        $view->render();
    } else {
        echo "No viewer by the name of $viewName";
    }

    //  Send exit on web request
    if ($_SERVER['HOST']) {
        exit();
    }
}

/**
 *  Log information
 *
 * @param string $output the log message
 * @param string $type the type of error message (DEBUG, INFO, WARNING, ERROR)
 * @param string $LogFile
 */
function logIt($output, $type = "INFO", $LogFile = LOG_FILE)
{
    global $request;
    global $startTime;
    $params     = NULL;
    $parameters = array();
    $route      = NULL;
    $action     = NULL;

    $processTime = round((microtime(TRUE) - $startTime) * 1000);

    if (is_object($request)) {
        $parameters = $request->parameters;
        $action     = $request->action;
        $route      = $request->route;
    }

    if (count($parameters) > 0) {
        foreach ($parameters as $index => $param) {
            if ($index == "password") {
                $parameters[$index] = "*****";
            }
        }

        $params = http_build_query($parameters);
    }

    if (!is_scalar($output)) {
        $output = print_r($output, TRUE);
    }

    if (preg_match("/$type/i", LOG_OPTIONS)) {
        $date = date('d-m-Y H:i:s');

        if (preg_match("/benchmark/", $LogFile)) {
            $log = sprintf("[%s] [%s] %s %s %s %s %s\n",
                $date,
                strtoupper($type),
                $processTime,
                $action,
                $route,
                $params,
                $output
            );
        } else {
            $log = sprintf("[%s] [%s] %s %s %s %s\n",
                $date,
                strtoupper($type),
                $processTime,
                $action,
                $route,
                $output
            );
        }

        if (LOG_PREFIX) {
            $log = LOG_PREFIX . " " . $log;
        }

        if (!$LogFile) {
            $LogFile = LOG_FILE;
        }

        error_log($log, 3, $LogFile);
    }
}

/**
 *  Create error message
 */
function errorOut($errorCode, $errorMessage = '', $extraInfo = NULL, $nonBlocking = FALSE)
{
    global $request;
    global $startTime;
    global $error;

    $error ['error_code'] = $errorCode;

    if ($errorMessage) {
        $error ['error_message'] = $errorMessage;
    }

    if ($extraInfo) {
        $error['error_info'] = $extraInfo;
    }

    logIt($error ['error_message'], "USER_ERROR");

    if (!$nonBlocking) {
        // Render to client
        $viewName = ucfirst($request->format) . 'View';
        if (class_exists($viewName)) {
            $processTime = round((microtime(TRUE) - $startTime) * 1000);
            $view        = new $viewName(FALSE, $processTime, $request->version, $error, TRUE);
            $view->render();
        }

        //  Send exit on web request
        if ($_SERVER['HOST']) {
            exit();
        }

        //  Throw exception for unit tests
        throw new RuntimeException($error["error_message"]);
    }
}

/**
 * Class autoloader
 *
 * @param string $classname
 *
 * @return bool
 */
function apiAutoload($classname)
{
    if (class_exists($classname)) {
        return TRUE;
    }

    if (preg_match('/[a-zA-Z]+Controller(|V[0-9]+)$/', $classname)) {
        if (file_exists(dirname(__FILE__) . '/../controllers/' . $classname . '.php')) {
            require_once(dirname(__FILE__) . '/../controllers/' . $classname . '.php');

        }

        return TRUE;
    } elseif (preg_match('/[a-zA-Z]+Model(|V[0-9]+)$/', $classname)) {
        if (file_exists(dirname(__FILE__) . '/../models/' . $classname . '.php')) {
            require_once(dirname(__FILE__) . '/../models/' . $classname . '.php');
        }

        return TRUE;
    } elseif (preg_match('/[a-zA-Z]+View(|V[0-9]+)$/', $classname)) {
        if (file_exists(dirname(__FILE__) . '/../views/' . $classname . '.php')) {
            require_once(dirname(__FILE__) . '/../views/' . $classname . '.php');
        }

        return TRUE;
    } elseif (preg_match('/[a-zA-Z]+Async(|V[0-9]+)$/', $classname)) {
        if (file_exists(dirname(__FILE__) . '/../AsyncJobs/' . $classname . '.php')) {
            require_once(dirname(__FILE__) . '/../AsyncJobs/' . $classname . '.php');
        }

        return TRUE;
    } else {
        if (file_exists(dirname(__FILE__) . '/../library/' . str_replace('_', DIRECTORY_SEPARATOR, $classname) . '.php')) {
            require_once(dirname(__FILE__) . '/../library/' . str_replace('_', DIRECTORY_SEPARATOR, $classname) . '.php');
        }

        return TRUE;
    }
}

/**
 *  Error handler
 *
 * @param $exception
 */
function exception_handler($exception)
{
    errorOut($exception->getCode(), $exception->getMessage());
}

//  Register error handler
set_exception_handler('exception_handler');

//  Register autoloader functions.
spl_autoload_register('apiAutoload');

//  Record process start time
$startTime = microtime(TRUE);

?>
