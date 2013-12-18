<?php

/**
 * Class CommonUtilities
 *
 * Common methods
 *
 * @author Nick Pettas <npettas@gmail.com>
 * @package rest-api
 */
class CommonUtilities
{
    /**
     *  Shuffle associated list
     *
     * @param $list
     *
     * @return array
     */
    public static function shuffleAssoc($list)
    {
        if (!is_array($list)) {
            return $list;
        }

        $keys = array_keys($list);
        shuffle($keys);
        $random = array();
        foreach ($keys as $key) {
            $random[] = $list[$key];
        }

        return $random;
    }

    /**
     *  Convert underscores to camel case
     *
     * @param $str
     *
     * @return string
     */
    public static function underscore2Camelcase($str)
    {
        // Split string in words.
        $words = explode('_', strtolower($str));

        $return = '';
        $i      = 0;
        foreach ($words as $word) {
            if ($i == 0) {
                $return .= trim($word);
            } else {
                $return .= ucfirst(trim($word));
            }

            $i++;
        }

        return $return;
    }

    /**
     *  Check if an email is valid.
     *
     * @param string $email
     *
     * @return bool
     * @author Tyler
     */
    public static function isValidEmail($email)
    {
        $email_regex = '/^(([^<>()[\]\\.,;:\s@\"]+(\.[^<>()[\]\\.,;:\s@\"]+)*)|(\".+\"))@((\[[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\])|(([a-zA-Z\-0-9]+\.)+[a-zA-Z]{2,}))$/';

        return (bool)(!is_null($email) and preg_match($email_regex, $email));
    }

    /*
     *  Create a curl call
     *  @param $url
     *  @param int $connect_timeout
     *  @param int $timeout
     *  @param int $return_transfer
     *  @return array
     */
    public static function genericCurl($url, $connect_timeout = 10, $timeout = 10, $return_transfer = 2)
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, TRUE);
        curl_setopt($ch, CURLOPT_TIMEOUT, $timeout);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, $connect_timeout);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, $return_transfer);
        $result['data']  = curl_exec($ch);
        $result['error'] = curl_errno($ch);
        $result['info']  = curl_getinfo($ch);
        if (isset($result)) {
            return $result;
        } else {
            return array();
        }
    }

    /**
     *  Get browser from ussr agent
     * @return string
     */
    public static function getBrowser()
    {
        $u_agent = $_SERVER['HTTP_USER_AGENT'];
        $ub      = '';
        if (preg_match('/MSIE/i', $u_agent)) {
            $ub = "ie";
        } elseif (preg_match('/Firefox/i', $u_agent)) {
            $ub = "firefox";
        } elseif (preg_match('/Chrome/i', $u_agent)) {
            $ub = "chrome";
        } elseif (preg_match('/Safari/i', $u_agent)) {
            $ub = "safari";
        } elseif (preg_match('/Flock/i', $u_agent)) {
            $ub = "flock";
        } elseif (preg_match('/Opera/i', $u_agent)) {
            $ub = "opera";
        }

        return $ub;
    }

    /**
     *  toCamelCase
     *
     * @param string $string
     * @param bool   $uc_first
     *
     * @return string
     */
    public static function toCamelCase($string, $uc_first = FALSE)
    {
        $parts = explode('_', $string);
        $final = strtolower(array_shift($parts));
        while ($word = array_shift($parts)) {
            $final .= ucfirst(strtolower($word));
        }
        if ($uc_first) {
            $final = ucfirst($final);
        }

        return $final;
    }

    /**
     * fromCamelCase
     *
     * @param string $string
     * @param bool   $leading_underscore
     *
     * @return string
     */
    public static function fromCamelCase($string, $leading_underscore = FALSE)
    {
        $search   = '/([A-Z])/';
        $callback = create_function('$matches',
            'return "_".strtolower(current($matches));');
        $result   = preg_replace_callback($search, $callback, $string);
        if (!$leading_underscore) {
            if (substr($result, 0, 1) == '_') {
                $result = substr($result, 1);
            }
        }

        return $result;
    }

    /**
     *  Convert csv string to array trims whitespace
     *
     * @param $ids
     *
     * @return array
     */
    public static function csvToArray($ids)
    {
        if (!is_array($ids)) {
            $ids = explode(",", $ids);
        }

        //  trim white space
        foreach ($ids as $key => $id) {
            $ids[$key] = stripslashes(trim($id));
        }

        if (count($ids) > 0 && $ids[0]) {
            return $ids;
        }

        return NULL;
    }

    /**
     *  Convert delimited strings to array trims whitespace
     *
     * @param $ids
     *
     * @return array
     */
    public static function stringToArray($ids)
    {
        if (!is_array($ids)) {
            $ids = preg_split("/[\s,]+/", $ids);
        }

        //  trim white space
        foreach ($ids as $key => $id) {
            $ids[$key] = stripslashes(trim($id));
        }

        if (count($ids) > 0 && $ids[0]) {
            return $ids;
        }

        return NULL;
    }

    /**
     *  Send json response to user
     *
     * @param $response
     */
    public static function returnJsonResponseToUser($response)
    {
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($response) . "\n";
        exit;
    }

    /**
     * Implode an array into a string
     *
     * @param        $keywords
     * @param string $delimiter
     *
     * @return string
     */
    public static function arrayToString($keywords, $delimiter = ",")
    {
        if (count($keywords) > 1) {
            $keywords = implode($delimiter, $keywords);
        } else {
            $keywords = $keywords[0];
        }

        return $keywords;
    }

    public static function jsonFormat($json)
    {
        $tab          = "  ";
        $new_json     = "";
        $indent_level = 0;
        $in_string    = FALSE;

        $json_obj = json_decode($json);

        if ($json_obj === FALSE) {
            return FALSE;
        }

        $json = CommonUtilities::jsonRemoveUnicodeSequences($json_obj);
        $len  = strlen($json);

        for ($c = 0; $c < $len; $c++) {
            $char = $json[$c];
            switch ($char) {
                case '{':
                case '[':
                    if (!$in_string) {
                        $new_json .= $char . "\n" . str_repeat($tab, $indent_level + 1);
                        $indent_level++;
                    } else {
                        $new_json .= $char;
                    }
                    break;
                case '}':
                case ']':
                    if (!$in_string) {
                        $indent_level--;
                        $new_json .= "\n" . str_repeat($tab, $indent_level) . $char;
                    } else {
                        $new_json .= $char;
                    }
                    break;
                case ',':
                    if (!$in_string) {
                        $new_json .= ",\n" . str_repeat($tab, $indent_level);
                    } else {
                        $new_json .= $char;
                    }
                    break;
                case ':':
                    if (!$in_string) {
                        $new_json .= ": ";
                    } else {
                        $new_json .= $char;
                    }
                    break;
                case '"':
                    if ($c > 0 && $json[$c - 1] != '\\') {
                        $in_string = !$in_string;
                    }
                default:
                    $new_json .= $char;
                    break;
            }
        }

        // $output = preg_replace('#\\\\#', "", $output);
        return preg_replace('#\\\\#', "", $new_json);
    }

    public static function jsonRemoveUnicodeSequences($struct)
    {
        return preg_replace("/\\\\u([a-f0-9]{4})/e", "iconv('UCS-4LE','UTF-8',pack('V', hexdec('U$1')))", json_encode($struct));
    }
}