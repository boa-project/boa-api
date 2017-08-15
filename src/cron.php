<?php

/**GENERAL CONFIGURATION*/
define('RESTOS_CLIENT_MODE', true);

include 'setup.php';

$rest = new RestGeneric(Restos::$Properties);

Restos::$DefaultRestGeneric = $rest;

//Init components
Restos::initComponents($rest);

$resources_path = 'resources';

$resources = scandir(Restos::$Properties->LocalPath . $resources_path);

$operations = array();
$operations[] = RestosLang::get('cron.init', null, date('c'));
if ($resources) {

    foreach($resources as $resource_dir) {
        $path = Restos::$Properties->LocalPath . $resources_path . '/' . $resource_dir;

        if ($resource_dir != '.' && $resource_dir != '..' && is_dir($path)) {

            if(Restos::using($resources_path . '.' . $resource_dir . '.cron_' . $resource_dir)) {
                $cron_class = 'cron_' . $resource_dir;

                $class = new $cron_class();

                $operation_result = null;
                try {
                    $operations[] = RestosLang::get('cron.init', null, $cron_class);
                    if ($class->execute()) {
                        $operation_result = RestosLang::get('cron.successful', null, $cron_class);
                    }
                    else {
                        $operation_result = RestosLang::get('cron.error', null, $cron_class);
                    }
                }
                catch(Exception $e) {
                    $params = new stdClass();
                    $params->class = $cron_class;
                    $params->code = $e->getCode();
                    $params->message = $e->getMessage();
                    if (defined('RESTOS_DEBUG_MODE') && RESTOS_DEBUG_MODE) {
                        $params->message .= "\n" . $e->getTraceAsString();
                        $operation_result = RestosLang::get('cron.exception', null, $params);
                    }
                }

                $operations[] = $class->OperationsLog;

                $operations[] = $operation_result;
            }
            
        }
    }
}
else {
    $operations = RestosLang::get('cron.notresources');
}

$operations[] = RestosLang::get('cron.end', null, date('c'));

print_r($operations);