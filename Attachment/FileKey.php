<?php

namespace c33s\AttachmentBundle\Attachment;

use Symfony\Component\DependencyInjection\Container;
use c33s\AttachmentBundle\Exception\InvalidKeyException;
use c33s\AttachmentBundle\Exception\MissingKeyParameterException;

class FileKey
{
    protected $storageName;
    protected $className;
    protected $fieldName;
    protected $depth;
    protected $hash;
    protected $extension;
    protected $key;
    protected $filePath;
    
    public function __construct()
    {
        
    }
    
    /**
     *
     * @param string $key
     *
     * @return FileKey
     */
    public static function fromKey($key)
    {
        $fileKey = new static();
        $fileKey->setKey($key);
        
        return $fileKey;
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
     * @return FileKey
     */
    public function setStorageName($storageName)
    {
        $this->storageName = $this->clean($storageName);
        
        return $this;
    }
    
    /**
     *
     * @return string
     */
    public function getClassName()
    {
        return $this->className;
    }
    
    /**
     *
     * @param string $className
     *
     * @return FileKey
     */
    public function setClassName($className)
    {
        $parts = explode('\\', $className);
        $name = end($parts);
        $this->className = $this->clean($name);
        
        return $this;
    }
    
    /**
     *
     * @return string
     */
    public function getFieldName()
    {
        return $this->fieldName;
    }
    
    /**
     *
     * @param string $fieldName
     *
     * @return FileKey
     */
    public function setFieldName($fieldName)
    {
        $this->fieldName = $this->clean($fieldName);
        if ('' === $this->fieldName)
        {
            $this->fieldName = 'general';
        }
        
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
     *
     * @return FileKey
     */
    public function setHash($hash)
    {
        $this->hash = $this->clean($hash);
        
        return $this;
    }
    
    /**
     *
     * @return string
     */
    public function getExtension()
    {
        return $this->extension;
    }
    
    /**
     *
     * @param string $extension
     *
     * @return FileKey
     */
    public function setExtension($extension)
    {
        $this->extension = $this->clean($extension);
        
        return $this;
    }
    
    /**
     *
     * @return string
     */
    public function getDepth()
    {
        return $this->depth;
    }

    /**
     *
     * @param int $depth
     *
     * @return FileKey
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
    public function getKey()
    {
        if (null === $this->key)
        {
            $this->key = $this->generateKey();
        }
        
        return $this->key;
    }
    
    /**
     *
     * @param string $key
     *
     * @return FileKey
     */
    protected function setKey($key)
    {
        $this->parseKey($key);
        
        return $this;
    }
    
    /**
     *
     * @return string
     */
    public function getDelimiter()
    {
        return '-';
    }
    
    /**
     * Clean string to use inside key.
     *
     * @param string $string
     *
     * @return string
     */
    protected function clean($string)
    {
        $string = str_replace($this->getDelimiter(), '_', Container::underscore($string));
        // fixes underscores removed by the Container::underscore()
        $string = str_replace('.', '_', $string);
        $string = preg_replace('/[^A-Za-z0-9_]/', '', $string);
        
        return preg_replace('/_+/', '_', $string);
    }
    
    /**
     *
     * @return string
     */
    public function getFilePath()
    {
        if (null === $this->filePath)
        {
            $this->filePath = $this->generateFilePath();
        }
        
        return $this->filePath;
    }
    
    /**
     * Generate file path from parameters that have been parsed from the key.
     *
     * @return string
     */
    protected function generateFilePath()
    {
        $hash = $this->getHash();
        
        $dir = '';
        
        for($i = 0; $i < $this->getDepth(); ++$i)
        {
            $dir .= $hash[$i].DIRECTORY_SEPARATOR;
        }
        
        if ('' != $this->getExtension())
        {
            $hash .= '.'.$this->getExtension();
        }
        
        $parts = array(
        	$this->getClassName(),
            $this->getFieldName(),
            $dir,
            $hash,
        );
        
        return implode(DIRECTORY_SEPARATOR, $parts);
    }
	
    /**
     * Generate key from parameters that have been set.
     *
     * @return string
     */
	protected function generateKey()
	{
	    if ('' == $this->getStorageName())
	    {
	        throw new MissingKeyParameterException('Cannot generate key without storage name');
	    }
	    if ('' == $this->getClassName())
	    {
	        throw new MissingKeyParameterException('Cannot generate key without class name');
	    }
	    if ('' == $this->getDepth())
	    {
	        throw new MissingKeyParameterException('Cannot generate key without depth');
	    }
	    if ('' == $this->getHash())
	    {
	        throw new MissingKeyParameterException('Cannot generate key without hash');
	    }
	    
	    $hash = $this->getHash();
	    
	    if ('' != $this->getExtension())
	    {
	        $hash .= '.'.$this->getExtension();
	    }
	    
	    $parts = array(
	        $this->getStorageName(),
	        $this->getClassName(),
	        $this->getFieldName(),
	        $this->getDepth(),
	        $hash,
	    );
	    
	    return implode($this->getDelimiter(), $parts);
	}
	
    /**
     * Parse existing key and split it into its parts.
     *
     * @param string $key
     * @throws InvalidKeyException
     *
     * @return FileKey
     */
    protected function parseKey($key)
    {
        if (substr_count($key, $this->getDelimiter()) != 4)
        {
            throw new InvalidKeyException('Invalid file key: '.$key);
        }
        
        $this->key = $key;
        
        list($storageName, $className, $fieldName, $depth, $hashExt) = explode($this->getDelimiter(), $key);
        
        if (false !== strpos($hashExt, '.'))
        {
            list($hash, $extension) = explode('.', $hashExt, 2);
        }
        else
        {
            $hash = $hashExt;
            $extension = '';
        }
        
        $this->storageName = $storageName;
        $this->className = $className;
        $this->fieldName = $fieldName;
        $this->depth = $depth;
        $this->hash = $hash;
        $this->extension = $extension;
        
        return $this;
    }
}
