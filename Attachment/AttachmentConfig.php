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
    
    public function __construct()
    {
        
    }
    
    /**
     * Initialize config object, passing raw AttachmentHandler config array, class name and field name to use.
     *
     * @param array $rawConfig
     * @param string $className
     * @param string $fieldName
     *
     * @return AttachmentConfig
     */
    public static function createFromRawConfig(array $rawConfig, $className, $fieldName)
    {
        $config = new static();
        
        $config
            ->setClassName($className)
            ->setFieldName($fieldName)
        ;
        
        $config->mergeConfigValues($rawConfig);
        
        if (isset($rawConfig['models'][$className]) && is_array($rawConfig['models'][$className]))
        {
            $config->mergeConfigValues($rawConfig['models'][$className]);
        }
        
        if (isset($rawConfig['models'][$className]['fields'][$fieldName]) && is_array($rawConfig['models'][$className]['fields'][$fieldName]))
        {
            $config->mergeConfigValues($rawConfig['models'][$className]['fields'][$fieldName]);
        }
        
        return $config;
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
        if (isset($configValues['storage_name']))
        {
            $this->setStorageName($configValues['storage_name']);
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
