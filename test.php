<?php

require_once __DIR__ . '/vendor/autoload.php';


$container = new \Pantono\Hydrator\Tests\MockObjects\TestContainer();
$hydrator = new \Pantono\Hydrator\Hydrator($container);
$test = new \Pantono\Hydrator\Tests\MockObjects\TestLocateModel();
var_Dump($hydrator->lookupRecord(\Pantono\Hydrator\Tests\MockObjects\TestLocateModel::class, 1));
exit;
