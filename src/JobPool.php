<?php namespace DataShaman;

use Pheanstalk\Pheanstalk;
use Predis\Client;

class JobPool
{
    public function __construct($prefix='job-pool')
    {
        $this->prefix = $prefix;
        $this->pheanstalk = new Pheanstalk('127.0.0.1');
        $this->redis = new Client;
        $this->objectFilter = new ObjectFilter;
    }

    public function pushData($data)
    {
        $this->putDataInTube('default', $data);
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
        return $this->objectFilter->checkFilter($data, $filter);
    }

    public function matchFilters($job)
    {
        $json = $job->getData();
        $data = json_decode($json, true);

        foreach ($this->getFilters() as $filterJson => $tube) {
            $filter = json_decode($filterJson, true);
            $match = $this->checkFilter($data, $filter);
            if ($match) {
                return $tube;
            }
        }
        return null;
    }

    public function getFilters()
    {
        return $this->redis->hgetall($this->generateKey('filters'));
    }

    public function dispatchLoop()
    {
        $this->workerLoop('default', function ($job) {
            $tube = $this->matchFilters($job);

            if (empty($tube)) {
                $this->releaseJob($job);
            } else {
                $this->putJobInTube($tube, $job);
                $this->deleteJob($job);
            }
        });
    }

    public function workerLoop($tube, $callback) {
        while (true) {
            $job = $this->reserveJob($tube);
            call_user_func($callback, $job);
        }
    }

    private function generateKey($key)
    {
        return $this->prefix.':'.$key;
    }
}
