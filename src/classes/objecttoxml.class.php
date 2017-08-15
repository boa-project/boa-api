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
 * Class ObjectToXML. Original of Jason Houle to PHPClasses, with some changes.
 *
 * @author Jason Houle
 * @link http://www.phpclasses.org/package/4657-PHP-Generate-XML-from-values-of-object-variables.html
 * @package Laberinto.WebServices.Restos
 * @version 0.1
 */
class ObjectToXML {

    private $dom;
    public $nameArrayElement = 'option';

    public function __construct($obj, $root_name = '') {
        $this->dom = new DOMDocument("1.0", "UTF8");
        
        if (empty($root_name)) {
            $root_name = get_class($obj);
        }

        $root = $this->dom->createElement($root_name);
        foreach ($obj as $key => $value) {
            $node = $this->createNode($key, $value);
            if ($node != NULL) {
                $root->appendChild($node);
            }
        }
        $this->dom->appendChild($root);
    }

    private function createNode($key, $value) {
        $node = NULL;
        if (is_string($value) || is_numeric($value) || is_bool($value) || $value == NULL) {
            if ($value == NULL) {
                $node = $this->dom->createElement($key);
            } else {
                $node = $this->dom->createElement($key, (string) $value);
            }
        }
        else if (is_array($value)) {
            $node = $this->dom->createElement($key);
            foreach ($value as $array_key => $array_value) {
                $node_key = is_numeric($array_key) ? is_object($array_value) ? get_class($array_value) : $this->nameArrayElement : $array_key;
                $sub = $this->createNode($node_key, $array_value);
                if ($sub != NULL) {
                    $node->appendChild($sub);
                }
            }
        }
        else {
            $node = $this->dom->createElement($key);
            if ($value != NULL) {
                foreach ($value as $key => $value) {
                    $sub = $this->createNode($key, $value);
                    if ($sub != NULL) {
                        $node->appendChild($sub);
                    }
                }
            }
        }
        return $node;
    }

    public function __toString() {
        return $this->dom->saveXML();
    }

}
