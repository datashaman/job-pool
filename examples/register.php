<?php
require_once __DIR__ . '/bootstrap.php';

use DataShaman\JobPool;

$jobPool = new JobPool;
$jobPool->pushFilter([ 'type' => 'waybill' ], 'deliver');
$jobPool->pushFilter([ 'type' => 'report' ], 'report');
