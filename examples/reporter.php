<?php
require_once __DIR__ . '/bootstrap.php';

use DataShaman\JobPool;

$pool = new JobPool;

$pool->workerLoop('report', function ($job, $pool) {
    $data = $pool->getData($job);
    echo "Reporting: ".json_encode($data)."\n";
    $pool->deleteJob($job);
});
