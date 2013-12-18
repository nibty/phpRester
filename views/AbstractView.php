<?php

/**
 * Api viewer class
 *
 * @package api-framework
 * @author Nick Pettas <npettas@gmail.com>
 */

class AbstractView
{
    public $version;
    public $error;
    public $return;
    public $success;
    public $processTime;
    public $httpCode;

    //  HTTP error codes
    private $httpCodes = array(
        204 => 'No Content',
        400 => 'Bad Request',
        401 => 'Unauthorized',
        403 => 'Forbidden',
        404 => 'Not Found',
        405 => 'Method Not Allowed',
        408 => 'Request Timeout',
        413 => 'Request Entity Too Large',
        414 => 'Request-URI Too Long',
        415 => 'Unsupported Media Type',
        500 => 'Internal Server Error',
        503 => 'Service Unavailable',
        504 => 'Gateway Timeout',
        509 => 'Bandwidth Limit Exceeded',
    );

    private $errorCodes = array(
        1  => 500,
        2  => 400,
        3  => 400,
        4  => 500,
        5  => 401,
        6  => 500,
        8  => 401,
        9  => 401,
        10 => 401,
        14 => 400,
        18 => 401,
        19 => 400,
        28 => 400,
        29 => 400,
        30 => 400,
        31 => 400
    );

    public function __construct($return, $processTime, $version = NULL, $error = NULL, $httpError = FALSE)
    {
        $this->setVersion($version);
        $this->setError($error);
        $this->setProcessTime($processTime);
        $this->isSuccess();
        $this->buildApiOutput($return);
        $this->setHttpCode($httpError);
    }

    /**
     * Builds api output
     *
     * @param $return the results from the api controller
     */
    public function buildApiOutput($return)
    {
        //  Add API header
        //$this->addOutput('version', $this->version);
        $this->addOutput('success', $this->success);
        $this->addOutput('process_time', $this->processTime);
        $this->addOutput('result', NULL);

        //  Return error if error occurred
        if ($this->error) {
            $this->addOutput('error', $this->error);
        }

        //  Merge in global return
        if ($this->return) {
            $this->addOutput('result', $return);
        }
    }

    /**
     *  Set success var
     */
    public function isSuccess()
    {
        if ($this->error) {
            $this->success = FALSE;
        } else {
            $this->success = TRUE;
        }
    }

    /**
     *  Set return
     */
    public function setReturn($return)
    {
        $this->return = $return;
    }

    /**
     *  Set the error
     */
    public function setError($error)
    {
        $this->error = $error;
    }

    /**
     *  Set http error
     */
    public function setHttpCode($error)
    {
        if ($error) {
            $httpCode    = $this->errorCodes[$this->error['error_code']];
            $httpMessage = $this->httpCodes[$this->errorCodes[$this->error['error_code']]];
            header("HTTP/1.1 {$httpCode} {$httpMessage}");
        }
    }

    /**
     *  Set the version number
     */
    public function setVersion($version)
    {
        $this->version = $version;
    }

    /**
     *  Set the status. True or false
     */
    public function setSuccess($success)
    {
        $this->success = $success;
    }

    /**
     *  Set process time
     */
    public function setProcessTime($processTime)
    {
        $this->processTime = $processTime;
    }

    /**
     *  Add to output
     */
    public function addOutput($key, $value)
    {
        $this->return [$key] = $value;
    }
}
