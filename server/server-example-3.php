<?php

require_once '../src/autoload.php';
require_once '../classes/HTML5.php';

$sql = "SELECT 
            p.`id`, 
            p.`nombre`,
            p.`apellido_paterno` as ape_paterno,
            p.`apellido_materno`,
            u.`email`,
            p.`fecha_nacimiento`
        FROM `personas` p 
        INNER JOIN `usuarios` u ON p.`id_usuario` = u.`id`
        WHERE p.`id` > :num";

$param = array(':num' => 0);

$dt = new DataTables;

$dt->query($sql, $param);

$dt->edit('ape_paterno', function ($data) {
    return $data['ape_paterno'] . ' ' . $data['apellido_materno'];
});

$dt->edit('fecha_nacimiento', function ($data) {
    return date('d/m/Y', strtotime($data['fecha_nacimiento']));
});

$dt->add('action', function ($data) {
    return HTML5::button([
        'type'          => 'button',
        'class'         => 'btn btn-primary btn-sm',
        'data-toggle'   => 'modal',
        'data-target'   => '#exampleModal',
        'text'          => '<i class="fas fa-search"></i>',
    ]);
});

$dt->hide('apellido_materno');

$response = $dt->generate()->toJson();
echo $response;
