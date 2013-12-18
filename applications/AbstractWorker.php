<?php

/**
 * Class AbstractWorker
 *
 * @author Nick Pettas <npettas@gmail.com>
 * @package rest-api
 */

ini_set('memory_limit', '512M');
require_once(dirname(__FILE__) . '/../library/RestApi.php');
require_once(dirname(__FILE__) . '/../library/beanstalk/pheanstalk_init.php');

class AbstractWorker
{
    private $pheanstalk;
    private $tube;

    public function __construct($tube)
    {
        $this->tube = $tube;
        if (!$this->pheanstalk = AsyncQueue::ConnectToBeanStalk()) {
            logIt("Can not connect to beanstalk. Exiting..", "ERROR");
            exit;
        }
    }

    /**
     *  Start daemon
     */
    public function run()
    {
        $job  = NULL;

        while (TRUE) {

            //  Check memory
            $memory = memory_get_usage();
            if ($memory > 100000000) {
                logIt("Beanstalk memory overload. Exiting.", ERROR);
                exit;
            }

            logIt("watching tube ($this->tube)... ", INFO);

            //  Decode job
            try {
                //  Get job
                $job = $this->pheanstalk->watch($this->tube)->ignore('default')->reserve();
                if (!is_object($job)) {
                    sleep(1);
                    logIt("bad job", ERROR);
                    continue;
                }

                $jobData = @$job->getData();
            } catch (Exception $e) {
                logIt("Unable to watch beanstalk tube", "ERROR");
                sleep(10);
                continue;
            }

            $this->pheanstalk->delete($job);


            try {
                logIt("Async job data " . print_r($jobData, TRUE));
                $this->runAsyncJob($jobData);
                logIt("Job complete");
            } catch (Exception $e) {
                logIt($e->getMessage(), "ERROR");
            }

            usleep(10);
        }
    }

    /**
     *  Run Api method
     *
     * @param AbstractController $object
     * @param string             $class the name of the class to call
     * @param string             $method the name of the method to call
     * @param array              $methodParams any method params
     *
     * @throws Exception
     * @return bool
     */
    private function runApiMethod(
        $object,
        $class,
        $method,
        $methodParams = NULL
    )
    {
        if (!class_exists($class)) {
            throw new Exception("beanstalk job failed. Class $class does not exists", 1);
        }

        $classObject = new $class;
        $classObject->setAction($object['action']);
        $classObject->setParams($object['params']);
        $classObject->setRoute($object['route']);

        // Make sure not to send back to queue
        $classObject->addParam("async_on", TRUE);

        if (method_exists($classObject, $method)) {
            try {
                $params = array($this->memcache);
                if ($methodParams && is_array($methodParams)) {
                    $params = array_merge($methodParams, $params);
                }
                call_user_func_array(array($classObject, $method), $params);
            } catch (Exception $e) {
                throw new Exception("beanstalk job failed " . $e->getMessage(), 1);
            }
        }

        return TRUE;
    }

    /**
     *  Run any method in this project
     *
     * @param      $class
     * @param      $method
     * @param null $methodParams
     * @param null $constructParams
     *
     * @throws Exception
     */
    private function runMethod(
        $class,
        $method,
        $methodParams = NULL,
        $constructParams = NULL
    )
    {
        if (!class_exists($class)) {
            throw new Exception("beanstalk job failed. Class $class does not exists", 1);
        }

        try {
            $classObject = new $class;
            $params      = array($this->memcache);

            if ($methodParams && is_array($methodParams)) {
                $params = array_merge($methodParams, $params);
            }
            call_user_func_array(array($classObject, $method), $params);

        } catch (Exception $e) {
            throw new Exception("beanstalk job failed " . $e->getMessage(), 1);
        }
    }

    /**
     *  Run async Job
     *
     * @param $jobData
     *
     * @throws Exception
     */
    public function runAsyncJob($jobData)
    {
        $jobData = json_decode($jobData, TRUE);

        //  Run job
        try {
            if ($jobData['type'] === "ApiCall") {
                $this->runApiMethod(
                    $jobData['object'],
                    $jobData['class'],
                    $jobData['method'],
                    $jobData['methodParams']
                );
            } elseif ($jobData['type'] === "MethodCall") {
                $this->runMethod(
                    $jobData['class'],
                    $jobData['method'],
                    $jobData['methodParams'],
                    $jobData['ConstructParams']
                );
            } else {
                throw new Exception("Beanstalk job failed. Invalid job type " . $jobData['type'], 1);
            }
        } catch (Exception $e) {
            logIt("there's a problem: " . $e->getMessage(), "ERROR");
            throw new Exception("Beanstalk job failed with exception", 1);
        }
    }

}
