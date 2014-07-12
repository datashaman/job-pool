<?php
require_once __DIR__ . '/bootstrap.php';

use DataShaman\JobPool;

$pool = new JobPool;

$pool->pushData([
    'type' => 'waybill',
    'customer' => 'Marlin Forbes',
]);
