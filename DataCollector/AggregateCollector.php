<?php

namespace Jns\Bundle\XhprofBundle\DataCollector;

use Symfony\Component\HttpFoundation\Request;

class AggregateCollector
{
    protected $collectors;

    public function addCollector(BaseCollector $collector)
    {
        $this->collectors[] = $collector;
    }

    public function startProfiling()
    {
        foreach ($this->collectors as $collector) {
            $collector->startProfiling();
        }
    }

    public function stopProfiling($serverName, $uri, Request $request = null)
    {
        $collected = 0;
        foreach ($this->collectors as $collector) {
            $collected += (int) $collector->stopProfiling($serverName, $uri, $request);
        }

        return $collected;
    }

    public function getUrl()
    {
        $urls = array();
        foreach ($this->collectors as $collector) {
            $urls[] = $collector->getUrl();
        }

        return implode(', ', $urls);
    }
}
