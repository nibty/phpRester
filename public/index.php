<?php

/**
 * API framework front controller
 *
 * @author Nick Pettas <npettas@gmail.com>
 * @package rest-api
 */

require_once(dirname(__FILE__) . '/../library/RestApi.php');

//  Create a request object
$request = new Request();

//  Generate controller name
$controllerName = ucfirst(CommonUtilities::underscore2Camelcase($request->controller)) . 'Controller';
if ($request->version > 1) {
    for ($i = $request->version; $i > 0; $i--) {
        if ($i == 1) {
            $controllerNameTest = $controllerName;
        } else {
            $controllerNameTest = $controllerName . "V" . $i;
        }

        if (class_exists($controllerNameTest)) {
            break;
        }
    }

    $controllerName = $controllerNameTest;
}

if (class_exists($controllerName)) {
    //  Run API Controller
    $controller = new $controllerName($request->action, $request->parameters, $request->route);
    $result     = $controller->getResults();

    // Output to user
    outputToUser($result);
} else {
    errorOut(1, "There's no controller by the name of $controllerName");
}

