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
 * Class RestGeneric
 *
 * @author David Herney <davidherney@gmail.com>
 * @package Laberinto.WebServices.Restos
 * @version 0.1
 */
class RestGeneric extends Entity {
    

    /**
     * 
     * Global object to manage the current response
     * @var RestResponse
     */
    public $RestResponse;
    
    /**
     * 
     * Global object to manage the received request
     * @var RestReceive
     */
    public $RestReceive;
    
    /**
     * 
     * Collection of general params for the site
     * @var object
     */
    private $_properties;

    /**
     * 
     * Contruct
     * Initialize the general params
     * @param $properties
     */
    public function __construct($properties){
        $this->_properties = $properties;
    }


    /**
     * Return a list of objects with the available resources
     * 
     * @return array 
     */
    public function getResourceList(){

        return Restos::$AvailableResources;
    }
    
    /**
     * Return a list of objects specifications with the available resources
     * 
     * @return array
     */
    public function getResourceListSpecifications(){

        $list = array();
        foreach($this->getResourceList() as $resource){
            $r = new SpecificationEntity();
            $r->TargetNamespace = new SpecificationNamespace('restresource', Restos::URIRest('restresource'));
            $r->seeAlso = Restos::URIRest($resource->Name);
            $r->about = $resource->Name;
            $list[$resource->Name] = $r;
        }
        
        return $list;
    }

    /**
     * Make an URI as a Rest resource
     * 
     * Posible URIs:
     * - http://server.com/relative_path/resource/id?params
     * - http://server.com/relative_path/index.php/resource/id?params
     * 
     * @param string $resource
     * @return string 
     */
    public function getURIRest($resource) {
        
        return Restos::URIRest($resource);
    }
    
    public function getResourcesURI (){
        //ToDO: Utilizar expresiones regulares para extraer los componentes de las URI
        // La expresión ofcicial es:  ^(([^:/?#]+):)?(//([^/?#]*))?([^?#]*)(\?([^#]*))?(#(.*))?
        // Cada posición de la expresión regular posee un dato de la URL 
        // tomada de http://labs.apache.org/webarch/uri/rfc/rfc3986.html#regexp

        
        if(isset($_SERVER['PATH_INFO'])){
            return ltrim($_SERVER['PATH_INFO'], '/');
        }
        else if(isset($_SERVER['ORIG_PATH_INFO'])){
            return ltrim($_SERVER['ORIG_PATH_INFO'], '/');
        }
        
        return '';
    }

    public function getProperty ($key){
    	$pos = -2;

    	$object = $this->_properties;
    	
    	do {
    		$start = $pos + 2;
    		$pos = strpos($key, '->', $start);
    		if ($pos === false) {
    		    $pos = strlen($key);
    		}
    		
    	    $property = substr($key, $start, $pos - $start);

    	    if(property_exists($object, $property)){
    		    $object = $object->$property;
    			if($pos == strlen($key)){
    			    return $object;
    			}
    		}
    		else {
    		    return null;
    		}
    	} while ($pos < strlen($key));
            	
    	return null;
    }
    
    /**
     * 
     * Return an object with data about a driver.
     * @param string $name
     * @param string $type Driver type, default is Resources
     * @return object
     */
    public function getDriverData ($name, $type = 'Resources') {
        $driver = $this->getProperty($type . 'Configuration->' . $name . '->Driver');
        
        if($driver && is_object($driver) && property_exists($driver, 'Name') && $driver->Name != null) {
            
            if (!property_exists($driver, 'Properties')) {
                $driver->Properties = null; 
            }
            return $driver;
        }
        
        $driver = null;
        $connector = $this->getProperty($type . 'Configuration->' . $name . '->DriverConnector');

        if($connector != null && is_string($connector)) {
            $driver = $this->getProperty('Connectors->' . $connector);

            if($driver && is_object($driver) && property_exists($driver, 'Name') && $driver->Name != null) {
                
                if (!property_exists($driver, 'Properties')) {
                    $driver->Properties = null; 
                }
            }
        }

        $connector_properties = $this->getProperty($type . 'Configuration->' . $name . '->Properties');
        
        if($connector_properties != null) {
            if ($driver == null) {
                $driver = new stdClass();
                $driver->Name = 'undefined';
                $driver->Properties = $connector_properties;
            }
            else {
                if (is_object($driver->Properties)) {
                    foreach((array)$connector_properties as $key=>$value) {
                        $driver->Properties->$key = $value;
                    }
                }
                else {
                    $driver->Properties = $connector_properties;
                }
            }
        }
        
        return $driver;
    }
    
    /**
     * 
     * Process the request resources and create the response data
     */
    public function processResources () {
        
        $resource_class = $this->RestReceive->getPrincipalResourceClass();
        
        $resource_controller = new $resource_class($this);
        
        // select data by verbe received
        switch(true){
            case $this->RestReceive->isGet():
                $implemented = $resource_controller->onGet();
                break;
            case $this->RestReceive->isPost():
                $implemented = $resource_controller->onPost();
                break;
            case $this->RestReceive->isPut():
                $implemented = $resource_controller->onPut();
                break;
            case $this->RestReceive->isDelete():
                $implemented = $resource_controller->onDelete();
                break;
            case $this->RestReceive->isOptions():
                $implemented = $resource_controller->onOptions();
                break;
            default:
                $implemented = false;
        }

        return $implemented;
    }
    
}

