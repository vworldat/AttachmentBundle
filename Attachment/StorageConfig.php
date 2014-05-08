<?php

namespace c33s\AttachmentBundle\Attachment;

use Gaufrette\Filesystem;
use c33s\AttachmentBundle\Exception\StorageDoesNotExistException;
use Symfony\Component\HttpFoundation\File\File;

/**
 * This is used by the AttachmentHandler to temporarily store storage config values.
 *
 * @author david
 */
class StorageConfig
{
    protected $fileKey;
    protected $key;
    protected $hash;
    protected $depth;
    protected $storageName;
    protected $filesystemName;
    protected $filesystem;
    protected $pathPrefix;
    protected $baseUrl;
    protected $basePath;
    protected $storagePath;
    
    public function __construct(FileKey $fileKey, array $rawStorageConfig)
    {
        $this->init($fileKey, $rawStorageConfig);
    }
    
    protected function init(FileKey $fileKey, array $rawStorageConfig)
    {
        $this->fileKey = $fileKey;
        
        $storageName = $fileKey->getStorageName();
        $this->setStorageName($storageName);
        
        if (!isset($rawStorageConfig[$storageName]))
        {
            throw new StorageDoesNotExistException('Invalid storage name: '.$storageName);
        }
        
        $this
            ->setFilesystemName($rawStorageConfig[$storageName]['filesystem'])
            ->setPathPrefix($rawStorageConfig[$storageName]['path_prefix'])
            ->setBaseUrl($rawStorageConfig[$storageName]['base_url'])
            ->setBasePath($rawStorageConfig[$storageName]['base_path'])
            ->setStoragePath($this->generateStoragePath())
        ;
    }
    
    /**
     * Generate the actual path to use inside the filesystem.
     */
    protected function generateStoragePath()
    {
        $path = $this->getFileKey()->getFilePath();
        $prefix = $this->getPathPrefix();
        
        return ltrim($prefix . '/' . $path, '/');
    }
    
    /**
     * @return FileKey
     */
    public function getFileKey()
    {
        return $this->fileKey;
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
        return trim($this->pathPrefix, '/');
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
        return rtrim($this->baseUrl, '/');
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
        
        return rtrim($this->getBaseUrl(), '/').'/'.$this->getStoragePath();
    }
    
    /**
     * Get File object to directly access the file.
     *
     * @return File
     */
    public function getFile()
    {
        if (!$this->hasBasePath())
        {
            throw new \RuntimeException('This storage does not have a configured base path!');
        }
        
        return new File($this->getBasePath() . DIRECTORY_SEPARATOR . $this->getStoragePath());
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

    /**
     *
     * @return string
     */
    public function getBasePath()
    {
        return rtrim($this->basePath, '/');
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
    public function setBasePath($basePath)
    {
        $this->basePath = $basePath;
        
        return $this;
    }
	
}
