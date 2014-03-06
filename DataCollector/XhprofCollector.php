<?php

namespace Jns\Bundle\XhprofBundle\DataCollector;

use Psr\Log\LoggerInterface as PsrLoggerInterface;

class XhprofCollector extends BaseCollector
{
    /**
     * {@inheritdoc}
     */
    public function startProfiling()
    {
        if (! $this->container->getParameter('jns_xhprof.xhprof.enabled')) {
            return false;
        }

        return parent::startProfiling();
    }

    /**
     * {@inheritdoc}
     */
    public function saveRun(array $data = array())
    {
        $runs = new \XHProfRuns_Default();

        return $runs->save_run($data, "Symfony");
    }

    /**
     * {@inheritdoc}
     */
    public function getUrl()
    {
        return $this->data['url'] . '?run=' . $this->data['xhprof'] . '&source=Symfony';
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return 'xhprof';
    }
}
