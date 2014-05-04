<?php

namespace c33s\AttachmentBundle\Attachment;

use Gaufrette\Filesystem;
use c33s\AttachmentBundle\Exception\StorageDoesNotExistException;

/**
 * This is used by the AttachmentHandler to temporarily store storage config values.
 *
 * @author david
 */
class StorageConfig
{
    protected $key;
    protected $hash;
    protected $depth;
    protected $storageName;
    protected $filesystemName;
    protected $filesystem;
    protected $pathPrefix;
    protected $baseUrl;
    protected $storagePath;
    
    public function __construct($key, array $rawStorageConfig, $keyDelimiter)
    {
        $this->init($key, $rawStorageConfig, $keyDelimiter);
    }
    
    protected function init($key, array $rawStorageConfig, $keyDelimiter)
    {
        list($hash, $depth, $storageName) = explode($keyDelimiter, $key, 3);
        
        $this
            ->setKey($key)
            ->setHash($hash)
            ->setDepth($depth)
            ->setStorageName($storageName)
        ;
        
        if (!isset($rawStorageConfig[$storageName]))
        {
            throw new StorageDoesNotExistException('Invalid storage name: '.$storageName);
        }
        
        $this
            ->setFilesystemName($rawStorageConfig[$storageName]['filesystem'])
            ->setPathPrefix($rawStorageConfig[$storageName]['path_prefix'])
            ->setBaseUrl($rawStorageConfig[$storageName]['base_url'])
            ->setStoragePath($this->generateStoragePath())
        ;
    }
    
    /**
     * Generate the actual path to use inside the filesystem.
     */
    protected function generateStoragePath()
    {
        $hash = $this->getHash();
        
        $dir = '';
        
        for($i = 0; $i < $this->getDepth(); ++$i)
        {
            $dir .= $hash[$i].DIRECTORY_SEPARATOR;
        }
        
        return sprintf('%s%s%s%s',
            ltrim($this->getPathPrefix(), '/'),
            DIRECTORY_SEPARATOR,
            $dir,
            $hash
        );
    }
    
    /**
     *
     * @return string
     */
    public function getKey()
    {
        return $this->key;
    }

    /**
     *
     * @param string $key
     */
    public function setKey($key)
    {
        $this->key = $key;
        
        return $this;
    }

    /**
     *
     * @return string
     */
    public function getHash()
    {
        return $this->hash;
    }

    /**
     *
     * @param string $hash
     */
    public function setHash($hash)
    {
        $this->hash = $hash;
        return $this;
    }

    /**
     *
     * @return int
     */
    public function getDepth()
    {
        return $this->depth;
    }

    /**
     *
     * @param int $depth
     */
    public function setDepth($depth)
    {
        $this->depth = (int) $depth;
        
        return $this;
    }

    /**
     *
     * @return string
     */
    public function getStorageName()
    {
        return $this->storageName;
    }

    /**
     *
     * @param string $storageName
     */
    public function setStorageName($storageName)
    {
        $this->storageName = $storageName;
        
        return $this;
    }

    /**
     *
     * @return string
     */
    public function getFilesystemName()
    {
        return $this->filesystemName;
    }

    /**
     *
     * @param string $filesystemName
     */
    public function setFilesystemName($filesystemName)
    {
        $this->filesystemName = $filesystemName;
        
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
    public function setFilesystem(Filesystem $filesystem)
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
    public function setPathPrefix($pathPrefix)
    {
        $this->pathPrefix = $pathPrefix;
        
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
    public function setBaseUrl($baseUrl)
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
     * Get the url to directly access this file.
     *
     * @return string
     */
    public function getFileUrl()
    {
        if (!$this->hasBaseUrl())
        {
            throw new \RuntimeException('This storage does not have a configured base url!');
        }
        
        return $this->getBaseUrl().'/'.$this->getStoragePath();
    }
    
    /**
     * The full path to the file inside the storage.
     *
     * @return string
     */
    public function getStoragePath()
    {
        return $this->storagePath;
    }

    /**
     *
     * @param string $storagePath
     */
    public function setStoragePath($storagePath)
    {
        $this->storagePath = $storagePath;
        
        return $this;
    }
}
