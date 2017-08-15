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
 
Restos::using('third_party.phpmailer.PHPMailerAutoload');

/**
 * Class oDataRestos. Process oData parameters received in request
 *
 * @author David Herney <davidherney@gmail.com>
 * @package Laberinto.WebServices.Restos
 * @version 0.1
 */

class RestosEmail extends PHPMailer {
    
    /**
     * Constructor
     * Initialize the general params
     */
    public function __construct(){
    
        $this->CharSet = 'UTF-8';

        if (property_exists(Restos::$Properties, 'Email')) {

            if (Restos::$Properties->Email->Type == 'SMTP') {
                // Set mailer to use SMTP
                $this->isSMTP();
                //$this->SMTPDebug = 2;
                $this->Host = Restos::$Properties->Email->SMTPHost;     // Specify main and backup SMTP servers
                $this->SMTPAuth = Restos::$Properties->Email->SMTPAuth; // If enable SMTP authentication
                
                if ($this->SMTPAuth) {
                    $this->Username = Restos::$Properties->Email->SMTPUsername; // SMTP username
                    $this->Password = Restos::$Properties->Email->SMTPPassword; // SMTP password
                }

                $this->SMTPSecure = Restos::$Properties->Email->SMTPSecure; // Enable encryption, tls and ssl accepted
                $this->Port = Restos::$Properties->Email->SMTPPort;         // TCP port to connect to
            }
            else if (Restos::$Properties->Email->Type == 'Sendmail') {
                // Set PHPMailer to use the sendmail transport
                $mail->isSendmail();
            }
            
            $this->From = Restos::$Properties->Email->From;
            $this->FromName = Restos::$Properties->Email->FromName;

            if (property_exists(Restos::$Properties->Email, 'ReplyTo')) {
                $this->addReplyTo(Restos::$Properties->Email->ReplyTo);
            }

        }
    }
}
