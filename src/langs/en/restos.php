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
 * String list from Spanish lang 
 *
 * @author David Herney <davidherney@gmail.com>
 * @package Laberinto.WebServices.Restos
 * @version 0.1
 */

$s['response.400.post.specificnotallowed'] = 'No se permiten recursos específicos para peticiones tipo POST';
$s['response.400.put.specificisrequired'] = 'Solo se permiten recursos específicos para peticiones tipo PUT';
$s['response.400.delete.specificisrequired'] = 'Solo se permiten recursos específicos para peticiones tipo DELETE';
$s['response.401.actionnotallowed'] = 'Esta acción no se encuentra disponible con su usuario actual o la sesión ha expirado.';
$s['post.notfound'] = 'No se encontraron datos';
$s['post.errorinsertdata'] = 'Ocurrió un error al insertar los datos';

$s['response.401.onlybasicauthorization'] = 'Solamente se soporta la autorización Básica. La cabecera debe ser de la forma [Authorization: Basic QWxhZGRpbjpvcGVuIHNlc2FtZQ==] acorde a la especificación http://www.ietf.org/rfc/rfc2617.txt';
$s['response.401.badcredentials'] = 'Las credenciales de autenticación se encuentran mal construidas';

$s['notfound'] = 'El recurso indicado no existe';
$s['save.baddata'] = 'No se pudo guardar la información porque alguno de los campos tiene un valor inválido';
$s['emptydata'] = 'No se pudo guardar la información porque no se recibió alguno de los campos obligatorios';

//Exceptions
$s['exception.nothandled'] = 'Ocurrió un error inesperado en el servidor';
$s['exception.objectnotfound'] = 'El objeto indicado no pudo ser encontrado';
$s['exception.drivernotavailable'] = 'Se ha especificado un driver que no está disponible';
$s['exception.drivernotconfigured'] = 'El driver $a no ha sido configurado correctamente';
$s['exception.drivernotexists'] = 'El driver $a no se encuentra en el sistema';
$s['exception.mappingnotsupported'] = 'El recurso no puede ser entregado en este formato: $a.';
$s['exception.baddatatype'] = 'El tipo de dato recibido no es válido';
$s['exception.id0notallowed'] = 'El identificador de recurso "0" o vacío no es válido';

//DB exceptions
$s['exception.db.error'] = 'Ah ocurrido un error desconocido en la base de datos';
$s['exception.db.uniqueviolation'] = 'Se ha intentado guardar un valor repetido en un campo único';
$s['exception.db.relationviolation'] = 'Se ha intentado guardar un valor con relación pero el valor relacionado no existe';
$s['exception.db.deleterelationviolation'] = 'Se ha intentado eliminar un valor con una relación que no lo permite';
$s['exception.db.cannotbenull'] = 'Un campo obligatorio no ha sido recibido';
$s['exception.db.entitynotexists'] = 'La entidad no existe';

//Cron
$s['cron.init'] = 'Iniciando la ejecución del cron en $a.';
$s['cron.end'] = 'La ejecución del cron ha finalizado en $a.';
$s['cron.initclass'] = 'Iniciando la ejecución del cron para la clase $a.';
$s['cron.successful'] = 'La ejecución del cron correspondiente a la clase $a terminó exitosamente.';
$s['cron.error'] = 'La ejecución del cron correspondiente a la clase $a terminó con errores.';
$s['cron.exception'] = 'La ejecución del cron correspondiente a la clase {$a->class} terminó con la excepción {$a->code}: {$a->message}.';
$s['cron.notresources'] = 'No hay recursos disponibles.';