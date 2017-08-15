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
 * Class RestClient provide methods to interact with rest resources in others servers
 *
 * @author David Herney <davidherney@gmail.com>
 * @package Laberinto.WebServices.Restos
 * @version 0.1
 */
class RestClient {
    
    private $_response_body;
    
    public $Errors = array();
    
    public function call ($url, $content_type = 'application/json', $method = 'GET', $data = null){

        $postdata = '';
        if (is_array($data)){
            $postdata = http_build_query($data);
        }
        
        $headers = array('cache-control: no-cache');
        if (!empty($content_type)){
            $headers[] ='Content-Type: ' . $content_type;
        }
        
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_HEADER, false);
        curl_setopt($ch, CURLOPT_TIMEOUT, 60);
        curl_setopt($ch, CURLOPT_USERAGENT, 'Restos');
        
        if ($method == 'POST') {
            curl_setopt($ch, CURLOPT_POST, true);        
            curl_setopt($ch, CURLOPT_POSTFIELDS, $postdata);
        }
        else if ($method == 'PUT') {
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'PUT'); 
            curl_setopt($ch, CURLOPT_POSTFIELDS, $postdata);
            $headers[] = 'Content-Length: ' . strlen($postdata); 
        }
        else {
            curl_setopt($ch, CURLOPT_POST, false);
        }
        
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        
        
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);

        $this->_response_body = curl_exec($ch);       

        // curl errors
        $curlerrno = curl_errno($ch);
        // HTTP error code
        $info =  curl_getinfo($ch);
        
        curl_close($ch);

        // check for curl errors
        if ($curlerrno != 0) {
            $this->Errors[] = "Request for $url failed with curl error $curlerrno";
            return false;
        }

        if (!empty($info['http_code']) && ($info['http_code'] < 200 || $info['http_code'] > 299)) {
            $this->Errors[] = "Request for " . $url . " failed with code " . $info['http_code'];
            return false;
        }

        return true;
    }
    
    public function getJson () {
        $json = @json_decode($this->_response_body);

        if(!is_object($json) && !is_array($json)) {
            return null;
        }
        
        return $json;
    }
}