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
 * Core function library and params setup
 *
 * @author David Herney <davidherney@gmail.com>
 * @package Laberinto.WebServices.Restos
 * @version 0.1
 */

/**GENERAL CONFIGURATION*/
define('RESTOS_DEBUG_MODE', true);

define('RESTOS_ABSOLUTE_PATH', str_replace('setup.php', '', str_replace('\\', '/', __FILE__)));


/*CODE*/
if (RESTOS_DEBUG_MODE) {
    ini_set('display_errors', 1);
    ini_set('error_reporting', E_ALL);
}

spl_autoload_register('restos__autoload');
set_error_handler('restos_exceptions_error_handler');

if (defined('RESTOS_CLIENT_MODE') && RESTOS_CLIENT_MODE) {
    Restos::$ExecutionType = Restos::EXECUTION_CLIENT;
}

$file_properties_name = 'properties.php';

Restos::initProperties($file_properties_name);
Restos::load();

if (property_exists(Restos::$Properties, 'SessionEnable') && Restos::$Properties->SessionEnable == true) {
    if (Restos::$ExecutionType == Restos::EXECUTION_CLIENT) {
        Restos::$Properties->SessionEnable == false;
    }
    else {
        session_start();
    }
}

//Default __autoload function
function restos__autoload($class_name) {

    $class_name = strtolower($class_name);

    //Normal classes
    if (file_exists(RESTOS_ABSOLUTE_PATH . 'classes/' . $class_name . '.class.php')) {
        include_once RESTOS_ABSOLUTE_PATH . 'classes/' . $class_name . '.class.php';
    }
    //Other cases as special classes and drivers
    else {
        
        switch ($class_name) {
            case 'drivermanager':
                include_once RESTOS_ABSOLUTE_PATH . 'drivers/drivermanager.class.php';
                break;
            case 'specificationentity':
            case 'specificationnamespace':
                include_once RESTOS_ABSOLUTE_PATH . 'resources/specificationentity.class.php';
                break;
            case 'restmapping':
                include_once RESTOS_ABSOLUTE_PATH . 'resources/restmapping.class.php';
                break;
            //Class RestResource is include before in the resource condition, this case is by order
            case 'restresource':
                include_once RESTOS_ABSOLUTE_PATH . 'resources/restresource.class.php';
                break;
            default:
                $pos = strpos($class_name, '_');
                
                if ($pos !== null) {
                    $type = substr($class_name, 0, $pos);
                    $name = substr($class_name, $pos + 1);
                    
                    switch ($type){
                        case 'driver':
                            if (file_exists(RESTOS_ABSOLUTE_PATH . 'drivers/' . $name . '/' . $class_name . '.class.php')) {
                                include_once RESTOS_ABSOLUTE_PATH . 'drivers/' . $name . '/' . $class_name . '.class.php';
                            }
                            break;
                        case 'restresource':
                            if (file_exists(RESTOS_ABSOLUTE_PATH . 'resources/' . $name . '/' . $class_name . '.class.php')) {
                                include_once RESTOS_ABSOLUTE_PATH . 'resources/' . $name . '/' . $class_name . '.class.php';
                            }
                        break;
                    }
                }
        }
    }
}

function restos_exceptions_error_handler($severity, $message, $filename, $lineno) {
  if (error_reporting() == 0) {
    return;
  }

  if (error_reporting() & $severity) {
    throw new ErrorException($message, 0, $severity, $filename, $lineno);
  }
}