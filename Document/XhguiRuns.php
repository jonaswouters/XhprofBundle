<?php

namespace Jns\Bundle\XhprofBundle\Document;

use iXHProfRuns;
use Doctrine\ODM\MongoDB\DocumentManager;

class XhguiRuns implements iXHProfRuns
{
    /**
     * @var DocumentManager
     */
    private $documentManager;

    public function __construct(DocumentManager $documentManager) {
        $this->documentManager = $documentManager;
    }

    /**
     * {@inheritDoc}
     */
    public function get_run($run_id, $type, &$run_desc) {
        throw new \Exception('not implemented');
    }

    /**
     * {@inheritDoc}
     */
    public function save_run($xhprof_data, $type, $run_id = null) {
        if (!class_exists('\Xhgui_Profiles') || !class_exists('\Xhgui_Saver_Mongo')) {
            throw new \Exception('composer require perftools/xhgui dev-master');
        }
        $data = $this->prepareForSave($xhprof_data);
        $dbname = $this->documentManager->getConfiguration()->getDefaultDB();
        $mongo = $this->documentManager->getConnection()->getMongoClient()->selectDB($dbname);
        $profiles = new \Xhgui_Profiles($mongo);
        $saver = new \Xhgui_Saver_Mongo($profiles);
        try {
            $saver->save($data);
            $run_id = (string)$data['_id'];
        } catch (\Exception $e) {
            error_log($e->getMessage());
        }
        return $run_id;
    }

    /**
     * @see https://github.com/perftools/xhgui/blob/ad532c42e55cf8b3413b8d7a2241eea1140b537f/external/header.php#L88
     * @todo instead of this copy pasta, refactor the perftools/xhgui side of things then reuse here
     */
    private function prepareForSave($xhprof_data) {
        $data = array('profile' => $xhprof_data);
        $uri = array_key_exists('REQUEST_URI', $_SERVER)
            ? $_SERVER['REQUEST_URI']
            : null;
        if (empty($uri) && isset($_SERVER['argv'])) {
            $cmd = basename($_SERVER['argv'][0]);
            $uri = $cmd . ' ' . implode(' ', array_slice($_SERVER['argv'], 1));
        }
        $time = array_key_exists('REQUEST_TIME', $_SERVER)
            ? $_SERVER['REQUEST_TIME']
            : time();
        $requestTimeFloat = explode('.', $_SERVER['REQUEST_TIME_FLOAT']);
        if (!isset($requestTimeFloat[1])) {
            $requestTimeFloat[1] = 0;
        }
        // if (Xhgui_Config::read('save.handler') === 'file') {
        //     $requestTs = array('sec' => $time, 'usec' => 0);
        //     $requestTsMicro = array('sec' => $requestTimeFloat[0], 'usec' => $requestTimeFloat[1]);
        // } else {
            $requestTs = new \MongoDate($time);
            $requestTsMicro = new \MongoDate($requestTimeFloat[0], $requestTimeFloat[1]);
        // }
        $data['meta'] = array(
            'url' => $uri,
            'SERVER' => $_SERVER,
            'get' => $_GET,
            'env' => $_ENV,
            'simple_url' => \Xhgui_Util::simpleUrl($uri),
            'request_ts' => $requestTs,
            'request_ts_micro' => $requestTsMicro,
            'request_date' => date('Y-m-d', $time),
        );
        return $data;
    }
}
