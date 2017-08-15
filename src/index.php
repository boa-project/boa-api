<?php


//Restos::$SlashURIs = false;
try {
    include 'setup.php';
    $rest = new RestGeneric(Restos::$Properties);
    $response = new RestResponse($rest);
}
catch (Exception $e) {
    header(HttpHeaders::getStatusCode('500'));

    $msg = 'Fatal app error.';

    if (RESTOS_DEBUG_MODE) {
        $msg .= $e->getMessage();
    }

    die($msg);
}

try {

    Restos::$DefaultRestGeneric = $rest;

    $receive  = new RestReceive($rest);

    $format = $receive->ResourceFormat;
    if (!empty($format)) {
        $response->Type = strtoupper($format);
    }

    //For global communication
    $rest->RestReceive   = $receive;
    $rest->RestResponse  = $response;


    //Init components
    Restos::initComponents($rest);

    //Resource URI validation
    $can = $receive->isValidResourceURI();

    //Current request URI is not available
    if (!$can) {
        $response->setHeader(HttpHeaders::$STATUS_CODE, HttpHeaders::getStatusCode('404'));
        $response->send();
        exit;
    }

    $resource_class = $receive->getPrincipalResourceClass();

    if(!class_exists($resource_class)) {
        $response->setHeader(HttpHeaders::$STATUS_CODE, HttpHeaders::getStatusCode('501'));
        $response->send();
        exit;
    }

    $implemented = $rest->processResources();

    if (!$implemented){
        $response->setHeader(HttpHeaders::$STATUS_CODE, HttpHeaders::getStatusCode('501'));
    }

}
catch (ApplicationException $ae) {

    $er = new stdClass();
    $er->error = true;
    $er->code = $ae->getCode();
    $er->message = $ae->getMessage();
    $er->managed = true;
    
    if (RESTOS_DEBUG_MODE && $ae->Original) {
        $er->info = $ae->Original->getMessage();
    }
    else {
        $er->info = '';
    }
    $response->setMessage($er, HttpHeaders::getStatusCode($ae->HttpStatusCode));
}
catch (ErrorException $ee) {
    $er = new stdClass();
    $er->error = true;
    $er->code = $ee->getCode();
    $er->message = $ee->getMessage();
    $er->managed = false;
    
    if (RESTOS_DEBUG_MODE) {
        $er->info = $ee->getTraceAsString();
    }
    else {
        $er->info = '';
    }
    $response->setMessage($er, HttpHeaders::getStatusCode(500));
}
catch (Exception $e) {
    $er = new stdClass();
    $er->error = true;
    $er->managed = false;
    $er->code = $e->getCode();

    if (RESTOS_DEBUG_MODE) {
        $er->message = 'apperror';
        $er->info = $e->getMessage() . "\n" . $e->getTraceAsString();
    }
    else {
        $er->message = RestosLang::get('exception.nothandled');
        $er->info = '';
    }

    $response->setMessage($er, HttpHeaders::getStatusCode('500'));
}

$response->send();
exit;
