<?php

/**
 * Request class
 *
 * @author Nick Pettas <npettas@gmail.com>
 * @package rest-api
 */

class Request
{
    public $route;
    public $object;
    public $parameters;
    public $version;
    public $format;

    public function __construct()
    {
        //  Detect format
        $this->format   = 'json';
        $formatElements = explode(".", $_SERVER['REQUEST_URI']);
        $format         = end($formatElements);
        if ($format == "json" || $format == "xml" || $format == "lbs" || $format == "cvs") {
            $this->format = $format;
        } else {
            $defaultFormat = TRUE;
        }

        //  Get request method
        $this->action = $_SERVER['REQUEST_METHOD'];

        //  Parse URI
        if (!$defaultFormat) {
            $route = str_replace("." . $format, "", $_SERVER['REQUEST_URI']);
        } else {
            $route = $_SERVER['REQUEST_URI'];
        }

        $urlElements      = explode('/', $route);
        $this->version    = (int)str_replace("v", "", $urlElements[2]);
        $pattern          = "#/" . $urlElements[1] . "/" . $urlElements[2] . "#";
        $this->route      = preg_replace($pattern, "", $route);
        $this->controller = $urlElements[3];

        //  Parse parameters
        $this->parseIncomingParams();

        // temp hack for tyler
        if ($this->version > 1) {
            $this->parameters['show_http_error_codes'] = 1;
        }

        return TRUE;
    }

    /**
     *  Parse for params
     */
    public function parseIncomingParams()
    {
        //error_log(print_r($_SERVER, true));
        $parameters = array();

        // Pull the GET vars
        if (isset($_SERVER['QUERY_STRING'])) {
            parse_str($_SERVER['QUERY_STRING'], $parameters);
        }

        // Get PUT/POST bodies. Override what we got from GET.
        $body         = file_get_contents("php://input");
        $content_type = FALSE;
        if (isset($_SERVER['CONTENT_TYPE'])) {
            $content_type = $_SERVER['CONTENT_TYPE'];
        }

        if (preg_match("#application/json#", $content_type)) {
            $body_params = json_decode($body);

            if ($body_params) {
                foreach ($body_params as $param_name => $param_value) {
                    $parameters[$param_name] = $param_value;
                }
            }
        } else {
            if ($body) {
                parse_str($body, $postvars);
            } else {
                $postvars = $_POST;
            }

            foreach ($postvars as $field => $value) {
                $parameters[$field] = $value;
            }

        }

        $this->parameters = $parameters;
    }

    public function parse_raw_http_request()
    {
        // read incoming data
        $input = file_get_contents('php://input');

        // grab multipart boundary from content type header
        preg_match('/boundary=(.*)$/', $_SERVER['CONTENT_TYPE'], $matches);
        $boundary = $matches[1];

        // split content by boundary and get rid of last -- element
        $a_blocks = preg_split("/-+$boundary/", $input);
        array_pop($a_blocks);

        // loop data blocks
        foreach ($a_blocks as $id => $block) {
            if (empty($block)) {
                continue;
            }

            // you'll have to var_dump $block to understand this and maybe replace \n or \r with a visibile char

            // parse uploaded files
            if (strpos($block, 'application/octet-stream') !== FALSE) {
                // match "name", then everything after "stream" (optional) except for prepending newlines
                preg_match("/name=\"([^\"]*)\".*stream[\n|\r]+([^\n\r].*)?$/s", $block, $matches);
            } // parse all other fields
            else {
                // match "name" and optional value in between newline sequences
                preg_match('/name=\"([^\"]*)\"[\n|\r]+([^\n\r].*)?\r$/s', $block, $matches);
            }
            $a_data[$matches[1]] = $matches[2];
        }

        return $a_data;
    }
}
