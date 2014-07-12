<?php namespace DataShaman;

use Pheanstalk\Pheanstalk;
use Predis\Client;
use Monolog\Logger;
use Monolog\Handler\TestHandler;

class JobPool
{
    public function __construct($tube='default', $prefix='job-pool')
    {
        $this->tube = $tube;
        $this->prefix = $prefix;
        $this->pheanstalk = new Pheanstalk('127.0.0.1');
        $this->redis = new Client;

        $logger = new Logger($prefix);
        $logger->pushHandler(new TestHandler);

        $this->arrayFilter = new ArrayFilter($logger);
    }

    public function pushData()
    {
        $data = call_user_func_array('array_merge', func_get_args());
        $this->putDataInTube($this->tube, $data);
    }

    public function getData($job)
    {
        return json_decode($job->getData(), true);
    }

    public function reserveJob($tube)
    {
        $job = $this->pheanstalk
            ->watchOnly($tube)
            ->reserve();
        return $job;
    }

    public function deleteJob($job)
    {
        $this->pheanstalk->delete($job);
    }

    public function releaseJob($job)
    {
        $this->pheanstalk->release($job);
    }

    public function putJobInTube($tube, $job)
    {
        $this->pheanstalk->putInTube($tube, $job->getData());
    }

    public function putDataInTube($tube, $data)
    {
        $this->pheanstalk->putInTube($tube, json_encode($data));
    }

    public function pushFilter($filter, $tube)
    {
        $this->redis->hset($this->generateKey('filters'), json_encode($filter), $tube);
    }

    public function checkFilter($data, $filter)
    {
        return $this->arrayFilter->checkFilter($data, $filter);
    }

    public function matchFilters($job)
    {
        $json = $job->getData();
        $data = json_decode($json, true);

        $tubes = array();

        foreach ($this->getFilters() as $filterJson => $tube) {
            $filter = json_decode($filterJson, true);
            $match = $this->checkFilter($data, $filter);
            if ($match) {
                $tubes[] = $tube;
            }
        }

        return array_unique($tubes);
    }

    public function getFilters()
    {
        return $this->redis->hgetall($this->generateKey('filters'));
    }

    public function dispatchLoop()
    {
        $this->workerLoop($this->tube, function ($job) {
            $tubes = $this->matchFilters($job);

            if (empty($tubes)) {
                $this->releaseJob($job);
            } else {
                foreach ($tubes as $tube) {
                    $this->putJobInTube($tube, $job);
                }
                $this->deleteJob($job);
            }
        });
    }

    public function workerLoop($tube, $callback) {
        while (true) {
            $job = $this->reserveJob($tube);
            call_user_func($callback, $job, $this);
        }
    }

    private function generateKey($key)
    {
        return $this->prefix.':'.$key;
    }
}
