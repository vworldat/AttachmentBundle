<?php

namespace C33s\AttachmentBundle\Storage;

use Gaufrette\Filesystem;
use Gaufrette\Adapter\Local;

/**
 * This is used by the AttachmentHandler to hold storage config values.
 *
 * @author david
 */
class Storage
{
    protected $name;
    protected $filesystem;
    protected $pathPrefix;
    protected $baseUrl;
    protected $basePath;
    protected $storagePath;
    
    public function __construct($name, $filesystem, $pathPrefix, $baseUrl, $basePath)
    {
        $this
            ->setName($name)
            ->setFilesystem($filesystem)
            ->setPathPrefix($pathPrefix)
            ->setBaseUrl($baseUrl)
            ->setBasePath($basePath)
        ;
    }
    
    /**
     *
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }
    
    /**
     *
     * @param string $storageName
     */
    protected function setName($name)
    {
        $this->name = $name;
        
        return $this;
    }
    
    /**
     *
     * @return Filesystem
     */
    public function getFilesystem()
    {
        return $this->filesystem;
    }

    /**
     *
     * @param string $filesystem
     */
    protected function setFilesystem(Filesystem $filesystem)
    {
        $this->filesystem = $filesystem;
        
        return $this;
    }

    /**
     *
     * @return string
     */
    public function getPathPrefix()
    {
        return $this->pathPrefix;
    }

    /**
     *
     * @param string $pathPrefix
     */
    protected function setPathPrefix($pathPrefix)
    {
        $this->pathPrefix = trim($pathPrefix, '/');
        
        return $this;
    }

    /**
     *
     * @return string
     */
    public function getBaseUrl()
    {
        return $this->baseUrl;
    }

    /**
     *
     * @param string $baseUrl
     */
    protected function setBaseUrl($baseUrl)
    {
        $this->baseUrl = rtrim($baseUrl, '/');
        
        return $this;
    }
    
    /**
     * Check if this storage has a base url.
     */
    public function hasBaseUrl()
    {
        return '' != $this->getBaseUrl();
    }
    
    /**
     * Check if this storage's Filesystem Adapter is the Local one.
     *
     * @return boolean
     */
    public function isLocal()
    {
        return $this->getFilesystem()->getAdapter() instanceof Local;
    }
    
    /**
     *
     * @return string
     */
    public function getBasePath()
    {
        return $this->basePath;
    }
    
    /**
     *
     * @return boolean
     */
    public function hasBasePath()
    {
        return null !== $this->basePath;
    }
    
    /**
     *
     * @param string $basePath
     *
     * @return StorageConfig
     */
    protected function setBasePath($basePath)
    {
        $this->basePath = rtrim($basePath, '/');
        
        return $this;
    }
}
