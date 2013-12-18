<?php

/**
 * Json view class
 *
 * @package api-framework
 * @author Nick Pettas <npettas@gmail.com>
 */

class JsonView extends AbstractView
{
    public $pretty;
    public $textArea;

    public function __construct($return, $processTime, $version = 1, $error = NULL, $httpError = FALSE,
                                $pretty = FALSE, $textArea = FALSE)
    {
        parent::__construct($return, $processTime, $version, $error, $httpError);
        $this->setPretty($pretty);
        $this->setTextArea($textArea);
    }

    /**
     *  Set json pretty format
     */
    public function setPretty($pretty)
    {
        if ($pretty == 'y' || $pretty == 1) {
            $this->pretty = 1;
        }
    }

    public function setTextArea($textArea)
    {
        if ($textArea) {
            $this->textArea = TRUE;
        } else {
            $this->textArea = FALSE;
        }
    }

    /**
     *  Json output to user
     *
     * @param output array to add to json output (optional)
     */
    public function render()
    {
        //  Json encode
        $output = json_encode($this->return);

        //  Make json pretty to the human eye
        if ($this->pretty) {
            $output = CommonUtilities::jsonFormat($output);
        }

        if ($this->textArea) {
            //  Output text/html
            header('Content-Type: text/html; charset=utf-8');
            echo "<textarea>" . $output . "</textarea>";
        } else {
            //  Output json
            header('Content-Type: application/json; charset=utf-8');
            echo $output . "\n";
        }
    }

}
