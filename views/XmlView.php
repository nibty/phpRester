<?php

/**
 * XML viewer class
 *
 * @package api-framework
 * @author Nick Pettas <npettas@gmail.com>
 */

class XmlView extends AbstractView
{
    public function __construct($processTime, $start, $version = 1,
                                $errorCode = NULL, $errorMessage = NULL, $pretty = FALSE)
    {
        parent::__construct($processTime, $version, $errorCode, $errorMessage);
    }

    /**
     * Json output to user
     */
    public function render()
    {
        // creating object of SimpleXMLElement
        $xmlReturn = new SimpleXMLElement("<?xml version=\"1.0\"?><root/>");

        // function call to convert array to xml
        $this->arrayToXml($this->return, $xmlReturn);

        header('Content-type: text/xml');
        echo $xmlReturn->saveXML();
    }

    /**
     * Convert array to xml
     *
     * @param $arrayIn
     * @param $xmlOut
     */
    public function arrayToXml($arrayIn, &$xmlOut)
    {
        foreach ($arrayIn as $key => $value) {
            if (is_array($value)) {
                if (!is_numeric($key)) {
                    $subnode = $xmlOut->addChild("$key");
                    $this->array_to_xml($value, $subnode);
                } else {
                    $this->array_to_xml($value, $xmlOut);
                }
            } else {
                $xmlOut->addChild("$key", "$value");
            }
        }
    }
}
