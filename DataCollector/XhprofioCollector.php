<?php

namespace Jns\Bundle\XhprofBundle\DataCollector;

use Symfony\Component\DependencyInjection\ContainerInterface;
use Doctrine\Bundle\DoctrineBundle\Registry as DoctrineRegistry;
use Symfony\Component\HttpFoundation\Request;

class XhprofioCollector extends BaseCollector
{
    protected $doctrine;
    protected $enabled;

    public function __construct(ContainerInterface $container, $logger = null, DoctrineRegistry $doctrine = null, $enabled = false)
    {
        parent::__construct($container, $logger);

        $this->doctrine = $doctrine;
        $this->enabled  = $enabled;
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
        $pmu = isset($data['main()']['pmu']) ? $data['main()']['pmu'] : '';
        $wt  = isset($data['main()']['wt'])  ? $data['main()']['wt']  : '';
        $cpu = isset($data['main()']['cpu']) ? $data['main()']['cpu'] : '';

        if (empty($this->doctrine)) {
            throw new \Exception("Trying to save to database, but Doctrine was not set correctly");
        }

        $runId = uniqid();

        $em = $this->doctrine->getManager($this->container->getParameter('jns_xhprof.xhprofio.manager'));
        $entityClass  = $this->container->getParameter('jns_xhprof.xhprofio.class');

        $xhprofDetail = new $entityClass();
        $xhprofDetail
            ->setId($runId)
            ->setUrl($request->getUri())
            ->setCanonicalUrl($this->getCanonicalUrl($request->getUri()))
            ->setServerName($request->getHost())
            ->setPerfData(gzcompress(serialize(($data))))
            ->setCookie(serialize($_COOKIE))
            ->setPost(serialize($_POST))
            ->setGet(serialize($_GET))
            ->setPmu($pmu)
            ->setWt($wt)
            ->setCpu($cpu)
            ->setServerId(getenv('SERVER_NAME'))
            ->setAggregateCallsInclude('')
            ->setTimestamp(new \DateTime())
        ;

        $em->persist($xhprofDetail);
        $em->flush();

        return $runId;
    }

    /**
     * Return the canonical URL for the passed in one.
     *
     * @param  String $url
     * @return String
     */
    protected function getCanonicalUrl($url)
    {
        if ($url[0] == '#') {
            $url = substr($url, 1, -1);

            if (substr($url, -1) == '$') {
                $url = substr($url, 0, -1);
            }
        }

        return $url;
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return 'xhprofio';
    }
}
