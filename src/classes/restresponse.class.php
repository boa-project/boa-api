<?php

/*
 *  This file is part of Restos software
 * 
 *  Restos is free software: you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License as published by
 *  the Free Software Foundation, either version 3 of the License, or
 *  (at your option) any later version.
 * 
 *  Restos is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 * 
 *  You should have received a copy of the GNU General Public License
 *  along with Restos.  If not, see <http://www.gnu.org/licenses/>.
 */

/**
 * Description of RestResponse
 *
 * @author David Herney <davidherney@gmail.com>
 * @package Laberinto.WebServices.Restos
 * @version 0.1
 */
class RestResponse extends Entity {
    
    private $_headers = array();
    public $Type = 'JSON';
    private $_content = null;
	
    public function __construct($rest_generic){
        return $this->RestResponse($rest_generic);
    }
    
    /**
     * Constructor
     * Initialize the general params
     */
    public function RestResponse($rest_generic) {
        
    }
    
    /**
     * Return the value of header identified by $key
     * 
     * @param string $key 
     * @return mixed
     */
    public function getHeader($key = ''){
        if (isset($this->_headers[$key])) {
            return $this->_headers[$key];
        }
        return false;
    }
    
    /**
     * Put a new header
     * 
     * @param string $key
     * @param string $value
     */
    public function setHeader($key, $value){
        $this->_headers[$key] = $value;
    }
    
    /**
     * Return the content
     * 
     * @return mixed 
     */
    public function getContent (){
        return $this->_content;
    }
    
    /**
     * The content as some type
     * 
     * @param mixed $value
     */
    public function setContent($value) {
        $this->_content = $value;
    }
    
    /**
     * Send the response to client
     * 
     */
    public function send(){
        
        foreach($this->_headers as $header){
            header($header);
        }

        $content = '';

        header('Content-type: ' . HttpHeaders::getContentType($this->Type));

        switch($this->Type) {
            case 'XML':
                if(!is_object($this->_content)){
                    if (is_array($this->_content)){
                        $tmp_content = new stdClass();
                        $tmp_content->data = $this->_content;
                    }
                    else {
                        if ($this->_content === false) {
                            $this->_content = "false";
                        }
                        else if ($this->_content === true) {
                            $this->_content = "true";
                        }
                        $tmp_content = new DOMDocument("1.0", "UTF8");
                        $root = $tmp_content->createElement('response', $this->_content);
                        $tmp_content->appendChild($root);
                    }
                    
                }
                else {
                    $tmp_content = $this->_content;
                }
                
                if (get_class($tmp_content) == 'DOMDocument'){
                    $content = $tmp_content->saveXML();
                }
                else {
                    Restos::using('classes.objecttoxml');

                    $root_name = strtolower(get_class($tmp_content)) == 'stdclass' ? 'response' : '';

                    $toxml = new ObjectToXML($tmp_content, $root_name);
                    $content = $toxml->__toString();
                }
            
                break;
            case 'JSON':
                $content = json_encode($this->_content);
                
                if (!is_array($this->_content) && !is_object($this->_content)) {
                    $content = '{ "response" : ' . $content . ' }';
                }
                break;
            case 'HTML':
            case 'TXT':
            default:
                $content = $this->_content;
        }
        
        if (!empty($content)) {

            if (is_object($content)) {
                foreach((array)$content as $key=>$val) {
                    echo $key . ': ' . var_export($val, true) . "\n";
                }
            }
            else if (is_array($content)) {
                foreach($content as $val) {
                    echo var_export($val, true);
                }
            }
            else {
                echo $content;
            }
        }
    }

    /**
     * Config a response message
     * 
     * @param string $value
     * @param string $status_code
     * @param string $type
     */
    public function setMessage($content, $status_code = null, $type = null) {
    
        if (!$status_code) {
            $status_code = HttpHeaders::getStatusCode('200');
        }
    
        $this->setHeader(HttpHeaders::$STATUS_CODE, $status_code);
        $this->Content = $content;
        
        if ($type) {
            $this->Type = $type;
        }
    }
}
