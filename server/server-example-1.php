<?php

require_once '../src/autoload.php';
require_once '../classes/HTML5.php';

$dt = new DataTables;
$dt->query('SELECT * FROM `sucursal` WHERE sucuestado IN(?, ?)', array(0, 1));
$response = $dt->generate()->toJson();
echo $response;
