<?php

namespace c33s\AttachmentBundle\Twig\Extension;

use c33s\AttachmentBundle\Attachment\AttachmentHandler;
use Liip\ImagineBundle\Imagine\Cache\CacheManager;

class AttachmentExtension extends \Twig_Extension
{
    /**
     * @var AttachmentHandler
     */
    protected $attachmentHandler;
    /**
     *
     * @var CacheManager
     */
    protected $cacheManager;
    
    /**
     * Create a new AttachmentExtension instance.
     *
     * @param AttachmentHandler $attachmentHandler
     */
    public function __construct(AttachmentHandler $attachmentHandler, CacheManager $cacheManager)
    {
        $this->attachmentHandler = $attachmentHandler;
        $this->cacheManager = $cacheManager;
    }
    
    public function getFilters()
    {
        return array(
            new \Twig_SimpleFilter('att_url', array($this, 'attachmentUrlFilter')),
            new \Twig_SimpleFilter('att_image', array($this, 'attachmentImageFilter'), array('is_safe' => array('html'))),
        );
    }
    
    /**
     * Fetch attachment url and pass through liip_imagine filter if the url is not null and filter name is given.
     *
     * @param string $key
     * @param string $filter    Optional liip_imagine filter name
     * @param boolean $absolute Return absolute url
     *
     * @return string
     */
    public function attachmentUrlFilter($key, $filter = null, $absolute = false)
    {
        if (empty($key))
        {
            return '';
        }
        
        $url = $this->attachmentHandler->getFileUrl($key);
        
        if (null === $filter)
        {
            return '';
        }
        
        return $this->cacheManager->getBrowserPath($url, $filter, $absolute);
    }
    
    /**
     * Filters attachment url, passes it through liip_imagine and provides a ready to use img tag that is marked as safe.
     * If the url is empty, no image tag will be generated.
     * Use this if you want to quickly output attachment images and not care if the attachment was set or not.
     *
     * @param string $key
     * @param string $filter
     * @param string $title
     * @param string $altImageUrl
     */
    public function attachmentImageFilter($key, $filter, $title = '', $altImageUrl = null)
    {
        $url = $this->attachmentUrlFilter($key, $filter);
        
        if ('' == $url && null === $altImageUrl)
        {
            return '';
        }
        
        return '<img src="'.('' != $url ? $url : $altImageUrl).'" title="'.htmlspecialchars($title).'" />';
    }
    
    public function getName()
    {
        return 'c33s_attachment';
    }
}
