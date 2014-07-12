<?php
require_once __DIR__ . '/bootstrap.php';

use DataShaman\JobPool;

$pool = new JobPool;

$pool->workerLoop('deliver', function ($job) use ($pool) {
    $data = $pool->getData($job);

    echo "Delivering: ".json_encode($data)."\n";
    $data['type'] = 'report';

    $pool->pushData($data);
    $pool->deleteJob($job);
});
