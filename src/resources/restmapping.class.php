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
 * Class to manage the resources mapping
 *
 * @author David Herney <davidherney@gmail.com>
 * @package Laberinto.WebServices.Restos
 * @version 0.1
 */
class RestMapping {
    
    /**
     * 
     * Object or objects array for mapping
     * @var object or array
     */
    protected $_data;
    
    /**
     * 
     * Name of root xml element
     * @var string
     */
    protected $_resourceLabel;
    
    /**
     * 
     * Name of root xml element if $_data is array
     * @var string
     */
    protected $_resourcesGroupLabel;
    
    /**
     * 
     * The XML Document to save the answer
     * @var DOMDocument
     */
    public $XmlDocument;
    
    /**
     * 
     * The Object to save the answer in JSon request
     * @var object
     */
    public $ObjectContent;
    
    /**
     * 
     * The HTML string to save the answer
     * @var string
     */
    public $Html;
    
    /**
     * 
     * Contruct
     * @param object or array $data
     * @param string $resource
     * @param string $resources_group
     */
    public function __construct ($data, $resource = 'resource', $resources_group = 'resources'){
        $this->_data = $data;
        $this->_resourceLabel = $resource;
        $this->_resourcesGroupLabel = $resources_group;
        $this->XmlDocument = new DOMDocument('1.0', 'UTF-8');
        $this->ObjectContent = new stdClass();
    }
    
    public function getMapping($type) {
        switch (strtoupper($type)){
            case 'XML':
                return $this->getXml();
            case 'JSON':
                return $this->getJson();
            case 'HTML':
                return $this->getHtml();
            case 'TXT':
                return $this->getTxt();
            default:
                throw new MappingNotSupportedException(RestosLang::get('exception.mappingnotsupported', 'restos', $type));
        }
    }
    
    /**
     * 
     * Create a XML document to response
     * @return DOMDocument
     */
    public function  getXml () {
        $namespaces = array();
        
        if(!is_array($this->_data)){
            if (is_object($this->_data) && get_class($this->_data) == 'stdClass') {
                $nodes = $this->getXmlNodeCollection($this->_resourceLabel, $this->XmlDocument, (array)$this->_data, $namespaces);
                $root = $nodes[0];
            }
            else {
                $root = $this->getXmlElement($this->_resourceLabel, $this->XmlDocument, $this->_data, $namespaces);
            }
            $this->XmlDocument->appendChild($root);
        }
        else {
            $nodes = $this->getXmlNodeCollection($this->_resourceLabel, $this->XmlDocument, $this->_data, $namespaces);
            
            $root = $this->XmlDocument->createElement($this->_resourcesGroupLabel);
            $root = $this->XmlDocument->appendChild($root);
            
            $root->setAttributeNode(new DOMAttr('rdf:about', Restos::URIRest($this->_resourcesGroupLabel)));

            foreach($nodes as $node){
                $root->appendChild($node);
            }
        }

        
        //$root->setAttributeNS('http://www.w3.org/2000/xmlns/', 'xmlns', Restos::URINamespace($this->_resourcesGroupLabel));
        $root->setAttribute('xmlns', Restos::URINamespace($this->_resourcesGroupLabel));
        $root->setAttributeNS('http://www.w3.org/2000/xmlns/', 'xmlns:rdf', 'http://www.w3.org/1999/02/22-rdf-syntax-ns#');
        $root->setAttributeNS('http://www.w3.org/2000/xmlns/', 'xmlns:rdfs', 'http://www.w3.org/2000/01/rdf-schema#');

        foreach ($namespaces as $namespace) {
            $root->setAttributeNS('http://www.w3.org/2000/xmlns/', 'xmlns:' . $namespace->PrefixNamespace, $namespace->URI);
        }

        return $this->XmlDocument;
    }

    /**
     * 
     * Create xml elements collection
     * 
     * @param string $element_type Tag for the element name
     * @param DomDocument $document
     * @param array $collection
     * @param array $namespaces
     * @return array or DOMElement
     */
    protected function getXmlNodeCollection($element_type, $document, $collection, &$namespaces, $isResource = false) {

        $nodes = array();

        $is_asociative = true;
        foreach(array_keys($collection) as $key){
            if (is_numeric($key)){
                $is_asociative = false;
                break;
            }
        }

        foreach($collection as $key => $data){
            
            if(isset($collection['CoreProperties']) && in_array($key, $collection['CoreProperties'])) {
                continue;
            }

            $tag_name = !$is_asociative ? $element_type : $key;
            if (is_object($data)) {
                /*$element = $this->getXmlElement($tag_name, $document, $data, $namespaces);
                
                if (!empty($data->about)) {
                    $element->setAttributeNode(new DOMAttr('rdf:about', $data->about));
                }
                
                $nodes[] = $element;*/
                $data = (array)$data;

            }
            
            if (is_array($data)){
                if (count($data) > 0) {

                    $inter_nodes = $this->getXmlNodeCollection($tag_name, $document, $data, $namespaces, $isResource);

                    foreach($inter_nodes as $inter){
                       $nodes[] = $inter;
                    }
                }
            }
            else {
                if ($isResource) {
                    $element = $document->createElement($tag_name);
                    $element->setAttributeNode(new DOMAttr('rdf:resource', $data));
                    $nodes[] = $element;
                }
                else {
                    if ($tag_name != 'about' && $tag_name != 'seeAlso') {
                        $nodes[] = $document->createElement($tag_name);
                        $nodes[count($nodes) - 1]->appendChild($document->createCDATASection($data));
                    }
                }
            }
        }

        if ($is_asociative) {
            $group_nodes = $document->createElement($element_type);
            
            if (!empty($collection['about'])) {
                $group_nodes->setAttributeNode(new DOMAttr('rdf:about', $collection['about']));
            }
            else if (!empty($collection['seeAlso'])) {
                $node = $document->createElement('rdfs:seeAlso');
                $node->setAttributeNode(new DOMAttr('rdf:resource', $collection['seeAlso']));
                $group_nodes->appendChild($node);
            }

            foreach ($nodes as $node) {
                $group_nodes->appendChild($node);
            }
            return array($group_nodes);
        }
        return $nodes;
    }
    
    /**
     * 
     * Create a xml element according to own properties
     * 
     * @param string $element_type Tag for the element name
     * @param DomDocument $document
     * @param object $entity
     * @param array $namespaces
     */
    protected function getXmlElement($element_type, $document, $entity, &$namespaces) {
        
        $global_prefix = '';
        if (property_exists($entity, 'TargetNamespace')) {
            $namespaces[] = $entity->TargetNamespace;
            $global_prefix = $entity->TargetNamespace->PrefixNamespace . ':';
        }
        
        if (property_exists($entity, 'Namespaces')) {
            $namespaces = array_merge($namespaces, $entity->Namespaces);
        }

        $element = $document->createElement($element_type);
        
        $reflection_user = new ReflectionClass($entity);
        $properties = $reflection_user->getProperties();

        foreach ($properties as $property){
            $prop_name = $property->getName();
            
            if (is_array($entity)) {
                if(isset($entity['CoreProperties']) && in_array($prop_name, $entity['CoreProperties'])) {
                    continue;
                }
            }
            else {
                if(isset($entity->CoreProperties) && in_array($prop_name, $entity->CoreProperties)) {
                    continue;
                }
            }

            $prefix = $global_prefix;
            
            //If the entity class define a namespaces collection
            if (property_exists($entity, 'Namespaces')) { 
                foreach ($entity->Namespaces as $some_namespace) {
                    if (in_array($prop_name, $some_namespace->Properties)) {
                        $prefix = $some_namespace->PrefixNamespace . ':';
                        break;
                    }
                }
            }

            $value = $entity->$prop_name;
            if (!empty($value) || $value === false || $value === 0) {
                if ($prop_name != 'about' && $prop_name != 'seeAlso') {
                    $isResource = false;
                    if (method_exists($entity, 'isResource') && $entity->isResource($prop_name)) {
                         $isResource = true;
                    }

                    if (is_array($value)) {
                        if (count($value) > 0) {
                            
                            $nodes = $this->getXmlNodeCollection($prefix . $prop_name, $document, $value, $namespaces, $isResource);
        
                            foreach($nodes as $node){
                                $element->appendChild($node);
                            }
                        }
                    }
                    else if (is_object($value)) {
                        $node = $this->getXmlElement($prefix . $prop_name, $document, $value, $namespaces);
                        $element->appendChild($node);
                    }
                    else {
                        if ($isResource) {
                            $node = $document->createElement($prefix . $prop_name);
                            $node->setAttributeNode(new DOMAttr('rdf:resource', $value));
                        }
                        else {
                            if ($value === false) {
                                $value = "false";
                            }
                            $node = $document->createElement($prefix . $prop_name);
                            $node->appendChild($document->createCDATASection($value));
                        }
                        $element->appendChild($node);
                    }
                }
            }
        }

        if (isset($entity->seeAlso) && $entity->seeAlso !== false) {
            if (!empty($entity->seeAlso)) {
                $node = $document->createElement('rdfs:seeAlso');
                $node->setAttributeNode(new DOMAttr('rdf:resource', $entity->seeAlso));
                $element->appendChild($node);
            }
            else if (!empty($entity->about)) {
                $node = $document->createElement('rdfs:seeAlso');
                $node->setAttributeNode(new DOMAttr('rdf:resource', Restos::URIRest($this->_resourcesGroupLabel . "/" . $entity->about)));
                $element->appendChild($node);
            }
        }
        

        return $element;
    }
    
    /**
     * 
     * Create an object or array to response with data for json encode
     * @return object or array
     */
    public function  getJson () {

        if(!is_array($this->_data)){
            if (is_object($this->_data) && get_class($this->_data) == 'stdClass') {
                $this->ObjectContent = $this->getObjectCollection((array)$this->_data);
            }
            else {
                $this->ObjectContent = $this->getObjectElement($this->_data);
            }
        }
        else {
            $this->ObjectContent = $this->getObjectCollection($this->_data);
        }

        return $this->ObjectContent;
    }

    /**
     * 
     * Create array of from collection
     * 
     * @param array $collection
     * @return array or object
     */
    protected function getObjectCollection($collection) {

        $nodes = array();

        foreach($collection as $key => $data){
            
            if (is_object($data)) {
                $data = (array)$data;
                //$nodes[$key] = $this->getObjectElement($data);
            }
            
            if (is_array($data)){
                if (count($data) > 0) {
                    $nodes[$key] = $this->getObjectCollection($data);
                }
                else {
                    $nodes[$key] = array();
                }
            }
            else {
                if (is_numeric($data)) {
                    if (strpos($data, '.') === false) {
                        $nodes[$key] = (int)$data;
                    }
                    else {
                        $nodes[$key] = (float)$data;
                    }
                }
                else {
                    $nodes[$key] = $data;
                }
            }
        }

        return $nodes;
    }
    
    /**
     * 
     * Create an object according to own properties
     * 
     * @param object $entity
     * @return object
     */
    protected function getObjectElement($entity) {
        
        $element = new stdClass();
        
        $reflection_user = new ReflectionClass($entity);
        $properties = $reflection_user->getProperties();
        
        foreach ($properties as $property){
            $prop_name = $property->getName();
            
            if (!in_array($prop_name, $entity->CoreProperties)) {

                $value = $entity->$prop_name;
                if (!empty($value) || $value === false || $value === 0) {
                    if ($prop_name != 'seeAlso' && ($prop_name != 'about' || $value !== false)) {
                        if (is_array($value)) {
                            if (count($value) > 0) {
                                $element->$prop_name = $this->getObjectCollection($value);
                            }
                            else {
                                $element->$prop_name = array();
                            }
                        }
                        else if (is_object($value)) {
                            $element->$prop_name = $this->getObjectElement($value);
                        }
                        else {
                            $element->$prop_name = $value;
                        }
                    }
                }
            }
        }

        if (isset($entity->seeAlso) && $entity->seeAlso !== false) {
            if (!empty($entity->seeAlso)) {
                $element->seeAlso = $entity->seeAlso;
            }
            else if (!empty($entity->about)) {
                $element->seeAlso = Restos::URIRest($this->_resourcesGroupLabel . "/" . $entity->about);
            }
        }

        return $element;
    }
    
    /**
     * 
     * Create a HTML document to response
     * @return string
     */
    public function getHtml() {      
        if(!is_array($this->_data)){
            if (is_object($this->_data) && get_class($this->_data) == 'stdClass') {
                $this->Html = $this->getHtmlCollection((array)$this->_data);
            }
            else {
                $this->Html = $this->getHtmlElement($this->_data);
            }
        }
        else {
            $this->Html = $this->getHtmlCollection($this->_data);
        }

        return $this->Html;
    }
    
    /**
     * 
     * Create a HTML for a collection
     * 
     * @param array $collection
     * @return string
     */
    protected function getHtmlCollection($collection) {
        $element = '<dl>';
        
        foreach($collection as $key => $data){
            if (is_object($data)) {
                //$element .= '<dt>' . $key . '</dt><dd>' . $this->getHtmlElement($data) . '</dd>';
                $data = (array)$data;
            }
            
            if (is_array($data)){
                if (count($data) > 0) {
                    $element .= '<dt>' . $key . '</dt><dd>' . $this->getHtmlCollection($data) . '</dd>';
                }
            }
            else {
                $element .= '<dt>' . $key . '</dt><dd>' . $data . "</dd>";
            }
        }
        
        $element .= '</dl>';
        
        return $element;
    }
    
    /**
     * 
     * Create a HTML for an object according to own properties
     * 
     * @param object $entity
     * @return string
     */
    protected function getHtmlElement($entity) {
        
        $element = '<dl>';
        
        $reflection_user = new ReflectionClass($entity);
        $properties = $reflection_user->getProperties();
        
        foreach ($properties as $property){
            $prop_name = $property->getName();
            
            if (!in_array($prop_name, $entity->CoreProperties)) {

                $value = $entity->$prop_name;
                
                if (!empty($value) || $value === false || $value === 0) {
                    if ($prop_name != 'seeAlso' && ($prop_name != 'about' || $value !== false)) {
                        if (is_array($value)) {
                            if (count($value) > 0) {
                                $element .= '<dt>' . $prop_name . '</dt><dd>' . $this->getHtmlCollection($value) . "</dd>";
                            }
                        }
                        else if (is_object($value)) {
                            $element .= '<dt>' . $prop_name . '</dt><dd>' . $this->getHtmlElement($value) . "</dd>";
                        }
                        else {
                            $element .= '<dt>' . $prop_name . '</dt><dd>' . $value . "</dd>";
                        }
                    }
                }
            }
        }
        
        if (isset($entity->seeAlso) && $entity->seeAlso !== false) {
            if (!empty($entity->seeAlso)) {
                $element .= '<dt>seeAlso</dt><dd>' . $entity->seeAlso . "</dd>";
            }
            else if (!empty($entity->about)) {
                $element .= '<dt>seeAlso:</dt><dd>' . Restos::URIRest($this->_resourcesGroupLabel . "/" . $entity->about) . "</dd>";
            }
        }
        
        $element .= '</dl>';

        return $element;
    }

    /**
     * 
     * Create a TXT document to response
     * @return string
     */
    public function getTxt() {
        if(!is_array($this->_data)){
            if (is_object($this->_data) && get_class($this->_data) == 'stdClass') {
                $this->Txt = $this->getTxtCollection((array)$this->_data);
            }
            else {
                $this->Txt = $this->getTxtElement($this->_data);
            }
        }
        else {
            $this->Txt = $this->getTxtCollection($this->_data);
        }

        return $this->Txt;
    }
    
    /**
     * 
     * Create a TXT for a collection
     * 
     * @param array $collection
     * @return string
     */
    protected function getTxtCollection($collection) {
        $element = "";
        
        foreach($collection as $key => $data){
            if (is_object($data)) {
                $data = (array)$data;
            }
            
            if (is_array($data)){
                if (count($data) > 0) {
                    $element .= '    ' . $key . "\n";
                    $element .= '        ' . $this->getTxtCollection($data) . "\n";
                }
            }
            else {
                $element .= '    ' . $key . "\n";
                $element .= '        ' . $data . "\n";
            }
        }
        
        $element .= "\n";
        
        return $element;
    }
    
    /**
     * 
     * Create a TXT for an object according to own properties
     * 
     * @param object $entity
     * @return string
     */
    protected function getTxtElement($entity) {
        
        $element = '';
        
        $reflection_user = new ReflectionClass($entity);
        $properties = $reflection_user->getProperties();
        
        foreach ($properties as $property){
            $prop_name = $property->getName();
            
            if (!in_array($prop_name, $entity->CoreProperties)) {

                $value = $entity->$prop_name;
                
                if (!empty($value) || $value === false || $value === 0) {
                    if ($prop_name != 'seeAlso' && ($prop_name != 'about' || $value !== false)) {
                        if (is_array($value)) {
                            if (count($value) > 0) {
                                $element .= '    ' . $prop_name . "\n";
                                $element .= '        ' . $this->getHtmlCollection($value) . "\n";
                            }
                        }
                        else if (is_object($value)) {
                            $element .= '    ' . $prop_name . "\n";
                            $element .= '        ' . $this->getHtmlElement($value) . "\n";
                        }
                        else {
                            $element .= '    ' . $prop_name . "\n";
                            $element .= '        ' . $value . "\n";
                        }
                    }
                }
            }
        }
        
        if (isset($entity->seeAlso) && $entity->seeAlso !== false) {
            if (!empty($entity->seeAlso)) {
                $element .= "    seeAlso\n";
                $element .= '        ' . $entity->seeAlso . "\n";
            }
            else if (!empty($entity->about)) {
                $element .= "    seeAlso\n";
                $element .= '        ' . Restos::URIRest($this->_resourcesGroupLabel . "/" . $entity->about) . "\n";
            }
        }
        
        $element .= "\n";

        return $element;
    }

}

/**
 *
 * Custom exception to indicate when a mapping type is not supported
 */
class MappingNotSupportedException extends Exception { }
