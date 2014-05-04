<?php

namespace c33s\AttachmentBundle\Attachment;

/**
 * This is used by the AttachmentHandler to temporarily store attachment config values.
 *
 * @author david
 */
class AttachmentConfig
{
    protected $className;
    protected $fieldName;
    protected $storageName;
    protected $hashCallable;
    protected $storageDepth;
    
    /**
     * Initialize config object, passing raw AttachmentHandler config array (attachments section), class name and field name to use.
     *
     * @param array $attachmentConfig
     * @param string $className
     * @param string $fieldName
     *
     * @return AttachmentConfig
     */
    public function __construct(array $attachmentConfig, $className, $fieldName)
    {
        $this->init($attachmentConfig, $className, $fieldName);
    }
    
    protected function init(array $attachmentConfig, $className, $fieldName)
    {
        $this
            ->setClassName($className)
            ->setFieldName($fieldName)
        ;
        
        $this->mergeConfigValues($attachmentConfig);
        
        if (isset($attachmentConfig['models'][$className]) && is_array($attachmentConfig['models'][$className]))
        {
            $this->mergeConfigValues($attachmentConfig['models'][$className]);
        }
        
        if (isset($attachmentConfig['models'][$className]['fields'][$fieldName]) && is_array($attachmentConfig['models'][$className]['fields'][$fieldName]))
        {
            $this->mergeConfigValues($attachmentConfig['models'][$className]['fields'][$fieldName]);
        }
    }
    
    /**
     * Check the given $configValues array for specific keys and inject the values into the config object if they exist.
     *
     * @param array $configValues
     */
    protected function mergeConfigValues(array $configValues)
    {
        if (isset($configValues['hash_callable']))
        {
            $this->setHashCallable($configValues['hash_callable']);
        }
        if (isset($configValues['storage_depth']))
        {
            $this->setStorageDepth($configValues['storage_depth']);
        }
        if (isset($configValues['storage']))
        {
            $this->setStorageName($configValues['storage']);
        }
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
     *
     * @return AttachmentConfig
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
     *
     * @param string $hashCallable
     *
     * @return AttachmentConfig
     */
    public function setHashCallable($hashCallable)
    {
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
    public function setStorageDepth($storageDepth)
    {
        $this->storageDepth = $storageDepth;
        
        return $this;
    }

    /**
     * @return string
     */
    public function getClassName()
    {
        return $this->className;
    }

    /**
     * @param string $className
     *
     * @return AttachmentConfig
     */
    public function setClassName($className)
    {
        $this->className = $className;
        
        return $this;
    }

    /**
     * @return string
     */
    public function getFieldName()
    {
        return $this->fieldName;
    }

    /**
     * @param string $fieldName
     *
     * @return AttachmentConfig
     */
    public function setFieldName($fieldName)
    {
        $this->fieldName = $fieldName;
        
        return $this;
    }
}
