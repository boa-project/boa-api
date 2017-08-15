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
 * Class RestReceive. Process a service request received
 *
 * @author David Herney <davidherney@gmail.com>
 * @package Laberinto.WebServices.Restos
 * @version 0.1
 */

class RestReceive extends Entity {
    const _RESOURCE_CLASS_PREFIX = 'RestResource_';

    private $_headers;
    private $_content;
    private $_params = array();
    private $_keys = array();
    private $_uri;
    private $_method;
    private $_extension = null;
    private $_resources;
    private $_principal_resource = null;
    private $_odata = null;

    public $DefaultResource = 'index';

    /**
     * Constructor
     * Initialize the general params
     */
    public function __construct($rest_generic){
    
        $this->_method = strtoupper($_SERVER["REQUEST_METHOD"]);        
        $this->_uri = $rest_generic->getResourcesURI();
        $this->_headers = apache_request_headers();
        
        if (!empty($this->_uri)) {
            $resources = explode('/', $this->_uri);
            $last_resource = $resources[count($resources) - 1];
            
            if (strpos($last_resource, '.') !== false) {
            	
                $parts = explode('.', $last_resource);
                $this->_extension = array_pop($parts);

                //Usually, not exist "dot" in the resource name, but if exist the parts are implode with this character
                $resources[count($resources) - 1] = implode('.', $parts);
                
                $this->_keys['content_type'] = HttpHeaders::getContentType($this->_extension);
            }
            else if (isset($this->_headers['Content-Type'])){
                $receive_content_type = $this->_headers['Content-Type'];
                
                $this->_extension = HttpHeaders::getExtensionByContentType($receive_content_type);
                
            }
                        
            $resources_string = implode('/', $resources);
            $this->_resources = new RestReceiveResources($resources_string);
            
            $this->_principal_resource = $this->_resources->getPrincipalResource();

        }
        
        $content = '';
        if ($this->_method == 'GET') {
            $this->_params = $_GET;
        }
        else {
            if ($this->_method == 'POST') {
                $this->_params = $_POST;
            }
            
            $content = file_get_contents("php://input", "r");
        }

        $this->_content = $content;
        
        $this->_odata = new oDataRestos();
        if (isset($this->_params['$filter'])) {
            
            $this->_odata->Filter = $this->_params['$filter'];
            
            unset($this->_params['$filter']);
        }
         
        if (isset($this->_params['$orderby'])) {
            
            $this->_odata->OrderBy = $this->_params['$orderby'];
            
            unset($this->_params['$orderby']);
        }
    }
    
    /**
     * 
     * Return the content as an array, if is possible. The keys in the array are the name of the received parameters
     * @return array
     */
    public function getProcessedContent(){
        $res = array();
        
        if (!empty($this->_content)){
            if (isset($this->_headers['Content-Type']) && HttpHeaders::getExtensionByContentType($this->_headers['Content-Type']) == 'json') {
                $json = json_decode($this->_content);
                if ($json !== null) {
                   return (array)$json;
                }
            }

            $content = str_replace('&amp;', '&', $this->_content);
            parse_str($content, $res);
            
        }

        return $res;
    }

    /**
     * 
     * Return the OData received
     * @return object oDataRestos
     */
    public function getOData(){
        return $this->_odata;
    }
    
    /**
     * 
     * Return the original content
     * @return string
     */
    public function getContent(){
        return $this->_content;
    }

    /**
     * 
     * Return an array with parameters received according to method: POST or GET
     * @return array
     */
    public function getParameters() {
        return $this->_params;
    }
    
    /**
     * Return the content type of the request
     * @return string
     */
    public function getContentType () {
        if (!isset($this->_keys['content_type'])){
            $this->_keys['content_type'] = $this->_headers['Content-Type'];
        }

        return $this->_keys['content_type'];
    }

    /**
     * Return a header by key (if any)
     * @return string
     */
    public function getHeader ($key) {
        if (isset($this->_headers[$key])){
            return $this->_headers[$key];
        }

        return false;
    }
    
    /**
     * Return the principal resource name in the URI received
     * 
     * @return string 
     */
    public function getPrincipalResource(){
        return $this->_principal_resource;
    }

    /**
     * Set the principal resource name
     * 
     */
    public function setPrincipalResource($value){
        $this->_principal_resource = $value;
    }

    /**
     * Return the class name of the principal resource
     * 
     * @return string 
     */
    public function getPrincipalResourceClass(){
        return RestReceive::_RESOURCE_CLASS_PREFIX . $this->_principal_resource;
    }

    /**
     * Return a list of resources in a special object
     * 
     * @return RestReceiveResources
     */
    public function getResources(){
        return $this->_resources;
    }

    /**
     * Return the extension of the URI (if any)
     * @return string
     */
    public function getResourceFormat() {
        return $this->_extension;
    }
    
    /**
     * Return if the operation is GET
     * @return bool
     */
    public function isGet() {
        return $this->_method == 'GET';
    }
    
    /**
     * Return if the operation is POST
     * @return bool
     */
    public function isPost() {
        return $this->_method == 'POST';
    }
    
    /**
     * Return if the operation is PUT
     * @return bool
     */
    public function isPut() {
        return $this->_method == 'PUT';
    }
    
    /**
     * Return if the operation is DELETE
     * @return bool
     */
    public function isDelete() {
        return $this->_method == 'DELETE';
    }
    
    /**
     * Return if the operation is OPTIONS
     * @return bool
     */
    public function isOptions() {
        return $this->_method == 'OPTIONS';
    }

    /**
     * Return a method name
     * 
     * @return string
     */
    public function getMethodName(){
        return $this->_method;
    }


    /**
     * To validate if the current receive URI is available according to the resources configuration
     * 
     * @return string
     */
    public function isValidResourceURI(){
        $resource_type = $this->getPrincipalResource();
        if (empty($resource_type)){
            if (!$this->isGet()) {
                return false;
            }
            else {
                $resource_type = $this->DefaultResource;
                $this->setPrincipalResource($resource_type);
            }
        }

        $can = false;

        if (array_key_exists($resource_type, Restos::$AvailableResources)) {
            if (count(Restos::$AvailableResources[$resource_type]->Verbs) == 1 && Restos::$AvailableResources[$resource_type]->Verbs[0] == '*') {
                $can = true;
            }
            else {
                $current_resources = $this->getResources();

                if ($current_resources == NULL || count($current_resources->ResourcesTree) == 1) {
                    if(in_array($this->getMethodName(), Restos::$AvailableResources[$resource_type]->Verbs)) {
                        $can = true;
                    }
                    else {
                        if ($current_resources != NULL && $current_resources->isSpecificResources() && in_array($this->getMethodName() . ':1', Restos::$AvailableResources[$resource_type]->Verbs)) {
                            $can = true;
                        }
                    }
                }
                else {

                    //If can use all paths for the current resource 
                    if (in_array($this->getMethodName() . '/*', Restos::$AvailableResources[$resource_type]->Verbs)) {
                        $can = true;
                    }
                    else {
                        $resources_elements = array();
                        for ($i = 0; $i < (count($current_resources->ResourcesTree) - 1); $i++) {
                            $resources_elements[] = $current_resources->ResourcesTree[$i]->Resource;
                        }
                        $resources_path = implode('/', $resources_elements);

                        $specific = $current_resources->isSpecificResources() ? ':1' : '';

                        if (in_array($this->getMethodName() . $specific . '/' . $resources_path, Restos::$AvailableResources[$resource_type]->Verbs)) {
                            $can = true;
                        }
                    }

                }
            }
        }

        return $can;
    }
}

if( !function_exists('apache_request_headers') ) {
///
function apache_request_headers() {
  $arh = array();
  $rx_http = '/\AHTTP_/';
  foreach($_SERVER as $key => $val) {
    if( preg_match($rx_http, $key) ) {
      $arh_key = preg_replace($rx_http, '', $key);
      $rx_matches = array();
      // do some nasty string manipulations to restore the original letter case
      // this should work in most cases
      $rx_matches = explode('_', $arh_key);
      if( count($rx_matches) > 0 and strlen($arh_key) > 2 ) {
        foreach($rx_matches as $ak_key => $ak_val) $rx_matches[$ak_key] = ucfirst($ak_val);
        $arh_key = implode('-', $rx_matches);
      }
      $arh[$arh_key] = $val;
    }
  }
  return( $arh );
}
///
}
///
