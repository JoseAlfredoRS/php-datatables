<?php

require_once '../src/autoload.php';
require_once '../classes/HTML5.php';

$dt = new DataTables;

$dt->query('SELECT * FROM `sucursal` WHERE sucuestado = :estado AND sucucodigo LIKE :codigo', array(
    ':estado'   =>  1,
    ':codigo'   =>  '00%',
));

$dt->add('update', function ($data) {
    $code = $data['sucucodigo'];
    return HTML5::i([
        'class'     => 'fas fa-pen',
        'style'     => 'cursor: pointer; color: #1976d2',
        'onclick'   => 'alert("' . $code . '")',
        'title'     => 'Editar',
    ]);
});

$dt->add('delete', function ($data) {
    $code = $data['sucucodigo'];
    return HTML5::i([
        'class'     => 'fas fa-trash',
        'style'     => 'cursor: pointer; color: #d32f2f',
        'onclick'   => 'alert("' . $code . '")',
        'title'     => 'Eliminar',
    ]);
});

$dt->hide('sucuestado');
$dt->hide('sucucontcuenta');

$response = $dt->generate()->toJson();
echo $response;
