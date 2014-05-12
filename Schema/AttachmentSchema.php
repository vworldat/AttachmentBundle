<?php

namespace c33s\AttachmentBundle\Schema;

use c33s\AttachmentBundle\Exception\InvalidHashCallableException;
use c33s\AttachmentBundle\Storage\Storage;

/**
 * This is used by the AttachmentHandler to hold attachment config values.
 *
 * @author david
 */
class AttachmentSchema
{
    protected $storage;
    protected $hashCallable;
    protected $storageDepth;
    
    /**
     * Initialize.
     *
     * @throws InvalidHashCallableException
     *
     * @param Storage $storage
     * @param string $hashCallable
     * @param int $storageDepth
     *
     * @return AttachmentConfig
     */
    public function __construct(Storage $storage, $hashCallable, $storageDepth)
    {
        $this
            ->setStorage($storage)
            ->setHashCallable($hashCallable)
            ->setStorageDepth($storageDepth)
        ;
    }
    
    /**
     *
     * @return Storage
     */
    public function getStorage()
    {
        return $this->storage;
    }
    
    /**
     *
     * @param string $storageName
     *
     * @return AttachmentConfig
     */
    protected function setStorage(Storage $storage)
    {
        $this->storage = $storage;
        
        return $this;
    }
    
    /**
     *
     * @return string
     */
    public function getHashCallable()
    {
        return $this->hashCallable;
    }
    
    /**
     * Get the hash callable as string, combining an array if necessary.
     *
     * @return string
     */
    public function getHashCallableAsString()
    {
        if (is_array($this->hashCallable))
        {
            return implode(':', $this->hashCallable);
        }
        
        return $this->hashCallable;
    }
    
    /**
     * @throws InvalidHashCallableException
     *
     * @param string $hashCallable
     *
     * @return AttachmentConfig
     */
    protected function setHashCallable($hashCallable)
    {
        if (!is_callable($hashCallable))
        {
            throw new InvalidHashCallableException('Hash callable cannot be called: '.serialize($hashCallable));
        }
        
        $this->hashCallable = $hashCallable;
        
        return $this;
    }
    
    /**
     *
     * @return int
     */
    public function getStorageDepth()
    {
        return $this->storageDepth;
    }
    
    /**
     * @param int $storageDepth
     *
     * @return AttachmentConfig
     */
    protected function setStorageDepth($storageDepth)
    {
        $this->storageDepth = (int) $storageDepth;
        
        return $this;
    }
}
