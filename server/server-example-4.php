<?php

require_once '../src/autoload.php';
require_once '../classes/HTML5.php';

$dt = new DataTables;

$dt->query('SELECT id, email, password FROM `usuarios`');

$dt->edit('password', function ($data) {
    return '************';
});

$dt->add('action', function ($data) {
    return HTML5::div([
        'class' => 'badge badge-success text-wrap',
        'style' => 'width: 4rem',
        'text'  => 'Foto'
    ]);
});

$response = $dt->generate()->toJson();
echo $response;
