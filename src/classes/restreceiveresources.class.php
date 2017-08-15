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
 * Class to storage the tree of resources received in the request url
 *
 * @author David Herney <davidherney@gmail.com>
 * @package Laberinto.WebServices.Restos
 * @version 0.1
 */
class RestReceiveResources extends Entity {
    
    /**
     * 
     * Tree of resources names and ids of each resource
     * @var array
     */
    public $ResourcesTree = array();
    
    /**
     * 
     * Resources and values as object
     * @var object
     */
    public $Resources;

    /**
     * 
     * Construct
     * @param string $string_url_path A slice of url with resources 
     */
    public function __construct($string_url_path = '') {
        
        $this->Resources = new stdClass();
        if (!empty($string_url_path)) {
            $initial_parts = explode('/', $string_url_path);
            
            $parts = array();
            foreach($initial_parts as $one) {
                if (!empty($one)) {
                    $parts[] = $one;
                }
            }
            
            for ($i = 0; $i < count($parts); $i = $i + 2){

                $node = new stdClass();
                $node->Resource = $parts[$i];
                $node->Id = isset($parts[$i + 1]) ? $parts[$i + 1] : null;
                
                if ($node->Id === 0 || $node->Id === "0" || $node->Id === '') {
                    Restos::throwException(null, RestosLang::get('exception.id0notallowed'));
                }

                $this->ResourcesTree[] = $node;
                
                $this->Resources->{$parts[$i]} = $node->Id;
            }
            
        }
    }
    
    /**
     * 
     * Checks whether a specific resource is requested
     * @param integer $index
     * @return bool
     */
    public function isSpecificResources($index = null){
        if ($index === null) {
            $index = count($this->ResourcesTree) - 1;
        }
        return (isset($this->ResourcesTree[$index]) && isset($this->ResourcesTree[$index]->Id));
    }

    /**
     * 
     * Return the key of a specific resource
     * @param integer $index
     * @return string
     */
    public function getResourceId($index = null){
        if ($index === null) {
            $index = count($this->ResourcesTree) - 1;
        }

        if ($this->isSpecificResources($index)) {
            return $this->ResourcesTree[$index]->Id;
        }
        
        return NULL;
    }
    
    public function getPrincipalResource(){
        if(count($this->ResourcesTree) > 0) {
            return $this->ResourcesTree[count($this->ResourcesTree) - 1]->Resource;
        }

        return null;
    }
 
    public function getResourceName($index){
        if(count($this->ResourcesTree) > $index) {
            return $this->ResourcesTree[$index]->Resource;
        }

        return null;
    }
}