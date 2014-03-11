<?php

namespace Jns\Bundle\XhprofBundle\DataCollector;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\DependencyInjection\ContainerInterface;

class XhguiCollector extends BaseCollector
{
    protected $enabled;
    protected $url;

    public function __construct(ContainerInterface $container, $logger = null, $enabled = false, $url = null)
    {
        parent::__construct($container, $logger);

        $this->enabled = $enabled;
        $this->url     = $url;
    }

    /**
     * {@inheritdoc}
     */
    public function startProfiling()
    {
        if (! $this->enabled) {
            return false;
        }

        return parent::startProfiling();
    }

    /**
     * {@inheritdoc}
     */
    public function saveRun(array $data = array(), $serverName, $uri, Request $request = null)
    {
        $time = $request ? $request->server->get('REQUEST_TIME') : time();

        $data = array(
            'profile' => $data,
            'meta'    => array(
                'url' => $uri,
                'SERVER' => $request ? $request->server->all() : $_SERVER,
                'get' => $request ? $request->request->all() : $_GET,
                'env' => $_ENV,
                'simple_url' => preg_replace('/\=\d+/', '', $uri),
                'request_ts' => round($time),
                'request_date' => date('Y-m-d', round($time)),
            ),
        );

        $connection = new \MongoClient('mongodb://10.10.10.1');
        $collection = $connection->xhprof->results;

        $collection->insert($data);

        return $data['_id'];
    }

    public function getUrl()
    {
        return $this->data['url'] . '/run.php?id=' . $this->data['xhprof'];
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return 'xhgui';
    }
}
