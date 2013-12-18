<?php

/**
 * Class AsyncQueue
 *
 * Beanstalk queue methods. For adding jobs to the queue.
 *
 * @author Nick Pettas <npettas@gmail.com>
 * @package rest-api
 */

require_once(dirname(__FILE__) . '/../library/beanstalk/pheanstalk_init.php');

class AsyncQueue
{
    /**
     *  Send API request to queue
     *
     *  Just add:
     *  $args = func_get_args();
     *  AsyncQueue::AddApiCallToQueue($this, __CLASS__, __FUNCTION__, $args);
     *
     * @param        $object
     * @param        $class
     * @param        $method
     * @param null   $methodParams
     * @param bool   $showOutput
     *
     * @param string $tube
     *
     * @return bool
     */
    public static function AddApiCallToQueue(
        $object,
        $class,
        $method,
        $methodParams = NULL,
        $showOutput = TRUE,
        $tube = QUEUE_TUBE
    )
    {
        if ($object->getParam("async_on")) {
            return FALSE;
        }

        if (!$pheanstalk = AsyncQueue::ConnectToBeanStalk()) {
            return FALSE;
        }

        $queueData = array(
            "type"         => "ApiCall",
            "object"       => $object,
            "class"        => $class,
            "method"       => $method,
            "methodParams" => $methodParams,
        );

        try {
            $pheanstalk->useTube($tube)->put(json_encode($queueData));
        } catch (Exception $e) {
            logIt("AddMethodCallToQueue Con not connect to beanstalk", ERROR);

            return FALSE;
        }

        outputToUser(TRUE);
    }

    /**
     *  Send method call to queue
     *
     * @param string $class the name of the class to call
     * @param string $method the name of the method to call
     * @param array  $methodParams any method params
     * @param array  $constructParams and construct params
     * @param string $tube
     *
     * @return bool
     */
    public static function AddMethodCallToQueue(
        $class,
        $method,
        $methodParams = NULL,
        $constructParams = NULL,
        $tube = QUEUE_TUBE
    )
    {
        logIt("AddMethodCallToQueue ", DEBUG);
        if (!$pheanstalk = AsyncQueue::ConnectToBeanStalk()) {
            logIt("AddMethodCallToQueue Con not connect to beanstalk", ERROR);

            return FALSE;
        }

        $queueData = array(
            "type"            => "MethodCall",
            "class"           => $class,
            "method"          => $method,
            "methodParams"    => $methodParams,
            "constructParams" => $constructParams,
        );

        logIt("AddMethodCallToQueue ($tube) " . print_r($queueData, TRUE), DEBUG);
        try {
            $pheanstalk->useTube($tube)->put(json_encode($queueData));
        } catch (Exception $e) {
            logIt("AddMethodCallToQueue Con not connect to beanstalk", ERROR);

            return FALSE;
        }

        return TRUE;
    }

    /**
     *  Connects to beanstalk
     *
     * @return Pheanstalk object
     */
    public static function ConnectToBeanStalk()
    {
        try {
            $pheanstalk = new Pheanstalk_Pheanstalk(BEANSTALK_SERVER . ':' . BEANSTALK_PORT);
        } catch (Exception $e) {
            logIt("Failed to connect to beanstalk " . BEANSTALK_SERVER . ':' . BEANSTALK_PORT, ERROR);

            return FALSE;
        }

        return $pheanstalk;
    }

}