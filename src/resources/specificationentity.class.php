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
 * Base class to manage the resources specification
 *
 * @author David Herney <davidherney@gmail.com>
 * @package Laberinto.WebServices.Restos
 * @version 0.1
 */
class SpecificationEntity extends Entity {
    
    /**
     * about thing is the especification
     * 
     * @var string
     */
    public $about;
    
    /**
     * The URL when exist full information about the resource
     * If this is empty, the seeAlso property is assumed according to about property
     * 
     * @var string
     */
    public $seeAlso;

    /**
     * 
     * Properties that specify a resource (typicaly a URI)
     * @var array
     */
    public $Resources = array();

    /**
     * 
     * The namespace that describe the class
     * @var SpecificationNamespace
     */
    public $TargetNamespace;
    
    /**
     * 
     * The namespaces used to some properties
     *
     * @var array of SpecificationNamespace objects
     */
    public $Namespaces = array();

    /**
     * 
     * Properties that are not in the specification, they are of utility
     * @var array
     */
    public $CoreProperties = array('TargetNamespace', 'Namespaces', 'CoreProperties', 'Resources');
    
    public function __construct(){
        $rdfs = new SpecificationNamespace('rdfs', 'http://www.w3.org/2000/01/rdf-schema#');
        $rdfs->Properties = array('seeAlso');
        $this->Namespaces[] = $rdfs;
        
        $rdf = new SpecificationNamespace('rdf', 'http://www.w3.org/1999/02/22-rdf-syntax-ns#');
        $rdf->Properties = array('about');
        $this->Namespaces[] = $rdf;

    }
    
    /**
     * 
     * Define if the property is a resource
     * @param string $property
     * @return bool
     */
    public function isResource ($property) {
        return in_array($property, $this->Resources);
    }
}

class SpecificationNamespace extends Entity {
    
    /**
     * 
     * The prefix namespace that reference the class
     * @var string
     */
    public $PrefixNamespace = '';

    /**
     * 
     * The reference URI
     * @var string
     */
    public $URI = '';
    
    /**
     * 
     * Properties related with this namespace
     * @var array
     */
    public $Properties = array();
    
    /**
     * 
     * Constructor
     * @param $prefix
     * @param $uri
     */
    public function __construct($prefix, $uri){
        $this->PrefixNamespace = $prefix;
        $this->URI = $uri;
    }
    
}