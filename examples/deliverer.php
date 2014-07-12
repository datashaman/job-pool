<?php
require_once __DIR__ . '/bootstrap.php';

use DataShaman\JobPool;

$pool = new JobPool;

$pool->workerLoop('deliver', function ($job, $pool) {
    $data = $pool->getData($job);

    echo "Delivering: ".json_encode($data)."\n";

    $pool->pushData($data, [
        'type' => 'report',
    ]);

    $pool->deleteJob($job);
});
