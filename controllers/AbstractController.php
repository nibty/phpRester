<?php

/**
 * Boilerplate controller
 *
 * @author Nick Pettas <npettas@gmail.com>
 * @package rest-api
 */

abstract class AbstractController
{
    public $results;
    public $route;
    public $params;
    public $action;
    public $method;

    public function __construct($action, $params, $route = NULL)
    {
        $this->setAction($action);
        $this->setParams($params);
        $this->setRoute($route);
    }

    /**
     *  Set HTTP GET action
     */
    public function get($routePattern, $method)
    {
        if ($this->method) {
            //  method already set
            return FALSE;
        }

        if ($this->action === "GET" && $this->routeMatcher($routePattern) && !empty($method)) {
            $this->setMethod($method);
        }
    }

    /**
     *  set HTTP POST action
     */
    public function post($routePattern, $method)
    {
        if ($this->method) {
            //  method already set
            return FALSE;
        }

        if ($this->action === "POST" && $this->routeMatcher($routePattern) && !empty($method)) {
            $this->setMethod($method);
        }
    }

    /**
     *  Set HTTP PUT action
     */
    public function put($routePattern, $method)
    {
        if ($this->method) {
            //  method already set
            return FALSE;
        }

        if (($this->action === "PUT" || $this->action === "PATCH")
            && $this->routeMatcher($routePattern) && !empty($method)
        ) {
            $this->setMethod($method);
        }
    }

    /**
     *  Sets HTTP DELETE action
     */
    public function delete($routePattern, $method)
    {
        if ($this->method) {
            //  method already set
            return FALSE;
        }

        if ($this->action === "DELETE" && $this->routeMatcher($routePattern) && !empty($method)) {
            $this->setMethod($method);
        }
    }

    /**
     *  Set custom HTTP action
     */
    public function customAction($routePattern, $method, $customAction)
    {
        if ($this->method) {
            //  method already set
            return FALSE;
        }

        if ($customAction === $this->action && $this->routeMatcher($routePattern) && !empty($method)) {
            $this->setMethod($method);
        }
    }

    /**
     *  Set action
     */
    public function setAction($action)
    {
        $this->action = $action;
    }

    /**
     *  Get action
     */
    public function getAction()
    {
        return $this->action;
    }

    /**
     *  Sets params
     */
    public function setParams($params)
    {
        $this->params = $params;

        if (isset($params['user'])) {
            $this->findUser();
        }
    }

    /**
     *  Gets params
     */
    public function getParams()
    {
        return $this->params;
    }

    /**
     *  Get param
     */
    public function getParam($name, $filter = NULL, $default = NULL)
    {
        $param = $default;

        if (isset($this->params[$name])) {
            switch ($filter) {
                //  String
                case STRING:
                    $param = trim(stripslashes($this->params[$name]));
                    $param = filter_var($param, FILTER_SANITIZE_STRING, FILTER_FLAG_NO_ENCODE_QUOTES);
                    break;

                //  Integer
                case INTEGER:
                    $param = filter_var($this->params[$name], FILTER_SANITIZE_NUMBER_INT);
                    break;

                //  Float
                case FLOAT:
                    $param = filter_var($this->params[$name], FILTER_SANITIZE_NUMBER_FLOAT);
                    break;

                // Boolean
                case BOOLEAN:
                    if (preg_match("/^(1|true|yes|y)$/i", $this->params[$name])) {
                        $param = TRUE;
                    } else {
                        $param = FALSE;
                    }
                    break;

                // Email
                case EMAIL:
                    $param = filter_var($this->params[$name], FILTER_SANITIZE_EMAIL);
                    break;

                // String array
                case STRING_ARRAY:
                    if (is_array($this->params[$name])) {
                        foreach ($this->params[$name] as $index => $pa) {
                            $param[$index] = filter_var(
                                $pa,
                                FILTER_SANITIZE_STRING,
                                FILTER_FLAG_NO_ENCODE_QUOTES
                            );
                        }

                    }
                    break;

                //  Integer array
                case INTEGER_ARRAY:
                    if (is_array($this->params[$name])) {
                        foreach ($this->params[$name] as $index => $pa) {
                            $param[$index] = filter_var(
                                $pa,
                                FILTER_SANITIZE_NUMBER_FLOAT
                            );
                        }

                    }
                    break;

                //  Float array
                case FLOAT_ARRAY:
                    if (is_array($this->params[$name])) {
                        foreach ($this->params[$name] as $index => $pa) {
                            $this->params[$index] = filter_var(
                                $pa,
                                FILTER_SANITIZE_NUMBER_INT
                            );
                        }

                    }
                    break;

                //  NO filter
                case NULL:
                    $param = $this->params[$name];
                    break;

                //  User defined filter
                default:
                    filter_var($this->params[$name], $filter);
                    break;
            }
        }

        return $param;
    }

    /**
     *  The same as getParam but it sets the param as an instance variable in this class
     *
     * @param      $name
     * @param null $filter
     * @param null $default
     *
     * @return bool
     */
    public function setInstanceVarParam($name, $filter = NULL, $default = NULL)
    {
        if (isset($this->params[$name])) {
            $param        = CommonUtilities::underscore2Camelcase($this->params[$name]);
            $this->$param = $this->getParam($name, $filter, $default);

            return $this->$param;
        }

        return FALSE;
    }

    /**
     *   Set route
     */
    public function setRoute($route)
    {
        $this->route = $route;
    }

    /**
     *  Gets route
     */
    public function getRoute()
    {
        return $this->route;
    }

    /**
     *  Sets the method
     */
    public function setMethod($method)
    {
        if ($method && method_exists($this, $method)) {
            $this->method = $method;

            return TRUE;
        }

        errorOut(1, "Invalid request. Please check route and action");
    }

    /**
     *  Gets API results
     */
    public function getResults()
    {
        return $this->results;
    }

    /**
     *  Call API method
     */
    public function run()
    {
        if ($this->method && method_exists($this, $this->method)) {
            $this->results = call_user_func(array($this, $this->method));

            return TRUE;
        }

        errorOut(1, "Invalid request. Please check route ({$this->getRoute()}) and action ({$this->getAction()})");
    }

    /**
     *  Matches requested route to route Assign any subpatterns as params.
     */
    public function routeMatcher($routePattern)
    {
        if (preg_match_all("#$routePattern(|/)$#", $this->route, $matches)) {
            $keys = array_keys($matches);
            foreach ($keys as $name) {
                if (!is_numeric($name) && $matches[$name][0]) {
                    $this->addParam($name, $matches[$name][0]);
                }
            }

            return TRUE;
        }

        return FALSE;
    }

    /**
     *   Add param to params list
     */
    public function addParam($name, $value)
    {
        if ($name && $value) {
            if (!$this->params) {
                $this->params[$name] = $value;
            } else {
                $this->params = array_merge($this->params, array($name => $value));
            }

            if ($name == 'user') {
                $this->findUser();
            }

            return TRUE;
        }

        return FALSE;
    }
}
