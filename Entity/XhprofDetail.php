<?php

namespace Jns\Bundle\XhprofBundle\Entity;


use Doctrine\ORM\Mapping as ORM;


/**
 * @ORM\Table(name="xhprof")
 * @ORM\Entity
 * @ORM\HasLifecycleCallbacks
 */
class XhprofDetail
{
    /**
     * @var integer $id
     *
     * @ORM\Column(name="id", type="integer", nullable=false)
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    protected $id;

    /**
     * @ORM\Column(name="url", type="string", length=255, nullable=true)
     */
    protected $url;

    /**
     * @ORM\Column(name="c_url", type="string", length=255, nullable=true)
     */
    protected $canonicalUrl;

    /**
     * @ORM\Column(name="`server name`", type="string", length=64, nullable=true)
     */
    protected $serverName;

    /**
     * @ORM\Column(name="type", type="integer", length=4, nullable=true)
     */
    protected $type;

    /**
     * @ORM\Column(name="perfdata", type="text", nullable=false)
     */
    protected $perfData;

    /**
     * @ORM\Column(name="cookie", type="text", nullable=false)
     */
    protected $cookie;

    /**
     * @ORM\Column(name="post", type="text", nullable=false)
     */
    protected $post;

    /**
     * @ORM\Column(name="get", type="text", nullable=false)
     */
    protected $get;

    /**
     * @ORM\Column(name="pmu", type="integer", length=11, nullable=true)
     */
    protected $pmu;

    /**
     * @ORM\Column(name="wt", type="integer", length=11, nullable=true)
     */
    protected $wt;

    /**
     * @ORM\Column(name="cpu", type="integer", length=11, nullable=true)
     */
    protected $cpu;

    /**
     * @ORM\Column(name="server_id", type="string", length=3, nullable=false)
     */
    protected $serverId;

    /**
     * @ORM\Column(name="aggregateCalls_include", type="string", length=255, nullable=true)
     */
    protected $aggregateCallsInclude;

    /**
     * @ORM\Column(name="timestamp", type="datetime", length=3, nullable=false)
     */
    protected $timestamp;


    /**
     * Get id.
     *
     * @return id.
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Set id.
     *
     * @param id the value to set.
     */
    public function setId($id)
    {
        $this->id = $id;
        return $this;
    }

    /**
     * Get url.
     *
     * @return url.
     */
    public function getUrl()
    {
        return $this->url;
    }

    /**
     * Set url.
     *
     * @param url the value to set.
     */
    public function setUrl($url)
    {
        $this->url = $url;
        return $this;
    }

    /**
     * Get canonicalUrl.
     *
     * @return canonicalUrl.
     */
    public function getCanonicalUrl()
    {
        return $this->canonicalUrl;
    }

    /**
     * Set canonicalUrl.
     *
     * @param canonicalUrl the value to set.
     */
    public function setCanonicalUrl($canonicalUrl)
    {
        $this->canonicalUrl = $canonicalUrl;
        return $this;
    }

    /**
     * Get serverName.
     *
     * @return serverName.
     */
    public function getServerName()
    {
        return $this->serverName;
    }

    /**
     * Set serverName.
     *
     * @param serverName the value to set.
     */
    public function setServerName($serverName)
    {
        $this->serverName = $serverName;
        return $this;
    }

    /**
     * Get perfData.
     *
     * @return perfData.
     */
    public function getPerfData()
    {
        return $this->perfData;
    }

    /**
     * Set perfData.
     *
     * @param perfData the value to set.
     */
    public function setPerfData($perfData)
    {
        $this->perfData = $perfData;
        return $this;
    }

    /**
     * Get cookie.
     *
     * @return cookie.
     */
    public function getCookie()
    {
        return $this->cookie;
    }

    /**
     * Set cookie.
     *
     * @param cookie the value to set.
     */
    public function setCookie($cookie)
    {
        $this->cookie = $cookie;
        return $this;
    }

    /**
     * Get post.
     *
     * @return post.
     */
    public function getPost()
    {
        return $this->post;
    }

    /**
     * Set post.
     *
     * @param post the value to set.
     */
    public function setPost($post)
    {
        $this->post = $post;
        return $this;
    }

    /**
     * Get get.
     *
     * @return get.
     */
    public function getGet()
    {
        return $this->get;
    }

    /**
     * Set get.
     *
     * @param get the value to set.
     */
    public function setGet($get)
    {
        $this->get = $get;
        return $this;
    }

    /**
     * Get pmu.
     *
     * @return pmu.
     */
    public function getPmu()
    {
        return $this->pmu;
    }

    /**
     * Set pmu.
     *
     * @param pmu the value to set.
     */
    public function setPmu($pmu)
    {
        $this->pmu = $pmu;
        return $this;
    }

    /**
     * Get wt.
     *
     * @return wt.
     */
    public function getWt()
    {
        return $this->wt;
    }

    /**
     * Set wt.
     *
     * @param wt the value to set.
     */
    public function setWt($wt)
    {
        $this->wt = $wt;
        return $this;
    }

    /**
     * Get cpu.
     *
     * @return cpu.
     */
    public function getCpu()
    {
        return $this->cpu;
    }

    /**
     * Set cpu.
     *
     * @param cpu the value to set.
     */
    public function setCpu($cpu)
    {
        $this->cpu = $cpu;
        return $this;
    }

    /**
     * Get serverId.
     *
     * @return serverId.
     */
    public function getServerId()
    {
        return $this->serverId;
    }

    /**
     * Set serverId.
     *
     * @param serverId the value to set.
     */
    public function setServerId($serverId)
    {
        $this->serverId = $serverId;
        return $this;
    }

    /**
     * Get type.
     *
     * @return type.
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * Set type.
     *
     * @param type the value to set.
     */
    public function setType($type)
    {
        $this->type = $type;
        return $this;
    }

    /**
     * Get timestamp.
     *
     * @return timestamp.
     */
    public function getTimestamp()
    {
        return $this->timestamp;
    }

    /**
     * Set timestamp.
     *
     * @param timestamp the value to set.
     */
    public function setTimestamp($timestamp)
    {
        $this->timestamp = $timestamp;
    }

    public function __toString()
    {
        return $this->getId();
    }

    /**
     * @ORM\PrePersist
     */
    public function beforePersist()
    {
        $this->setTimestamp(new \DateTime());
    }

    /**
     * Get aggregateCallsInclude.
     *
     * @return aggregateCallsInclude.
     */
    public function getAggregateCallsInclude()
    {
        return $this->aggregateCallsInclude;
    }

    /**
     * Set aggregateCallsInclude.
     *
     * @param aggregateCallsInclude the value to set.
     */
    public function setAggregateCallsInclude($aggregateCallsInclude)
    {
        $this->aggregateCallsInclude = $aggregateCallsInclude;
        return $this;
    }
}
