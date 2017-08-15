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
 * Class HttpHeaders
 *
 * @author David Herney <davidherney@gmail.com>
 * @package Laberinto.WebServices.Restos
 * @version 0.1
 */
class HttpHeaders {
    
    public static $STATUS_CODE = 'status_code';
    public static $CONTENT_TYPE = 'content_type';
    public static $WWW_AUTHENTICATE = 'WWW-Authenticate';
    
    /**
     * List of valid status codes
     * http://www.w3.org/Protocols/rfc2616/rfc2616-sec10.html
     * 
     * @var array 
     */
    private static $_STATUS_CODES = array(
        200 => 'HTTP/1.1 200 OK',
        201 => 'HTTP/1.1 201 Created',
        202 => 'HTTP/1.1 202 Accepted',
        203 => 'HTTP/1.1 203 Non-Authoritative Information',
        204 => 'HTTP/1.1 204 No Content',
        205 => 'HTTP/1.1 205 Reset Content',
        206 => 'HTTP/1.1 206 Partial Content',
        400 => 'HTTP/1.1 400 Bad Request',
        401 => 'HTTP/1.1 401 Unauthorized',
        404 => 'HTTP/1.1 404 Not Found',
        405 => 'HTTP/1.1 405 Method Not Allowed',
        406 => 'HTTP/1.1 406 Not Acceptable',
        409 => 'HTTP/1.1 409 Conflict',
        500 => 'HTTP/1.1 500 Internal Server Error',
        501 => 'HTTP/1.1 501 Not Implemented'
    );
    
    private static $_CONTENT_TYPES = array(
        'XML' => 'text/xml',
        'JSON' => 'application/json',
        'HTML' => 'text/html',
        'PNG' => 'image/png'
    );

    
    /**
     *
     * @param int $code
     * @return string 
     */
    public static function getStatusCode($code){
        if (isset(HttpHeaders::$_STATUS_CODES[$code])){
            return HttpHeaders::$_STATUS_CODES[$code];
        }
        return '';
    }

    /**
     * Return a content type according to type (extension)
     * @param string $type
     * @return string 
     */
    public static function getContentType($type){
        $type = strtoupper($type);
       	return RestosMimeTypes::getMimeType($type);
    }

    /**
     * Return the type (extension) according to content type string
     *
     * @param string $type
     * @return string 
     */
    public static function getExtensionByContentType($type){
    	
        foreach(RestosMimeTypes::$_MIME_TYPES as $key => $content_type) {
			
            if (strpos(strtolower($type), $content_type) !== false) {
                return $key;
            }
        }
        
        return '';
    }
    
    /**
     * Return a header with special text for Basic Authenticate
     * @param string $realm
     * @return string 
     */
    public static function getBasicWWWAuthenticateRealm($realm){
       	return HttpHeaders::$WWW_AUTHENTICATE . ': Basic realm="' . $realm . '"';
    }

    /**
     * Build a custom header
     * @param string $name
     * @param string $value
     * @return string 
     */
    public static function getRestosCustom($name, $value){
       	return 'RESTOS-' . $name . ': ' . $value;
    }
}
