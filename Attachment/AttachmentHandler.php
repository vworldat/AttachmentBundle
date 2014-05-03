<?php

namespace c33s\AttachmentBundle\Attachment;

use c33s\AttachmentBundle\Model\Attachment;
use Symfony\Component\HttpFoundation\File\File;
use Knp\Bundle\GaufretteBundle\FilesystemMap;
use c33s\AttachmentBundle\Model\AttachmentQuery;
use c33s\AttachmentBundle\Model\AttachmentLink;

/**
 * AttachmentHandler is the service gapping the bridge between actual files (residing in Gaufrette storages)
 * and meta data (in Attachment database objects).
 *
 * @author david
 *
 */
class AttachmentHandler
{
    protected $rawConfig;
    protected $configs = array();
    protected $keyDelimiter = '-';
    
    public function __construct(array $rawConfig/*, FilesystemMap $filesystemMap*/)
    {
        $this->rawConfig = $rawConfig;
    }
    
    /**
     * Store a new attachment file for the given object and optional object field name.
     * There is no need for the field name to exist explicitly inside the object.
     *
     * @param File $file
     * @param AttachableObjectInterface $object
     * @param string $fieldName
     *
     * @return Attachment   The created Attachment object
     */
    public function storeAndAttachFile(File $file, AttachableObjectInterface $object, $fieldName = null)
    {
        $this->checkFile($file);
        
        $config = $this->getConfigForObject($object, $fieldName);
        $key = $this->generateKey($file, $config);
        
        $this->moveToStorage($file, $key);
        
        $attachment = new Attachment();
        $attachment
           ->setFileKey($key)
           ->setFileSize($file->getSize())
           ->setFileType($file->getMimeType())
           ->setStorageName($config->getStorageName())
           ->setStorageDepth($config->getStorageDepth())
        ;
        
        $link = new AttachmentLink();
        $link
           ->setAttachment($attachment)
           ->setModelName($object->getAttachableClassName())
           ->setModelId($object->getAttachableId())
           ->setModelField($fieldName)
           ->setFileName($file->getFilename())
           ->setFileExtension($file->getExtension())
        ;
        
        if (in_array($fieldName, $object->getAttachableFieldNames()))
        {
            $method = 'set'.$fieldName;
            
            if (!method_exists($object, $method))
            {
                throw new \RuntimeException('Fieldname setter for '.$fieldName.' does not exist in '.get_class($object));
            }
            
            $object->$method($key);
            $link->setIsCurrent(true);
        }
        
        /**
         * TODO: this will not work if the related object is new and does not have an id yet.
         */
        $link->save();
        
        return $attachment;
    }
    
    /**
     * Perform some checks to see if the given file is valid and ready to use.
     *
     * @throws \RuntimeException
     *
     * @param File $file
     */
    protected function checkFile(File $file)
    {
        if (!$file->isReadable())
        {
            throw new \RuntimeException('File is not readable');
        }
        
        if (!$file->isWritable())
        {
            throw new \RuntimeException('File is not writable');
        }
        
        return true;
    }
    
    /**
     * Fetch the config to use for the given object type and fieldname.
     *
     * @param AttachableObjectInterface $object
     * @param string $fieldName
     *
     * @return AttachmentConfig
     */
    protected function getConfigForObject(AttachableObjectInterface $object, $fieldName)
    {
        $className = $object->getAttachableClassName();
        $configKey = sprintf('%s:%s', $className, $fieldName);
        
        if (!isset($this->configs[$configKey]))
        {
            $this->configs[$configKey] = AttachmentConfig::createFromRawConfig($this->rawConfig, $className, $fieldName);
        }
        
        return $this->configs[$configKey];
    }
    
    /**
     * Move the file to the storage as defined in the given file key.
     *
     * @param File $file
     * @param string $key
     */
    protected function moveToStorage(File $file, $key)
    {
        $path = $this->keyToStoragePath($key);
    }
    
    /**
     * Convert the given key into a storage path.
     *
     * @param string $key
     *
     * @return string
     */
    public function keyToStoragePath($key)
    {
        list($hash, $depth, $storageName) = explode($this->getKeyDelimiter(), $key, 3);
    }
    
    /**
     * Generate the file key for the given file path and config.
     *
     * The key has the following format:
     * [file hash] [delimiter] [folder depth] [delimiter] [storage name]
     *
     * @param File $file
     * @param AttachmentConfig $config
     */
    protected function generateKey(File $file, AttachmentConfig $config)
    {
        $hashCallable = $config->getHashCallable();
        
        if (!is_callable($hashCallable))
        {
            throw new \RuntimeException('Invalid file hashing callable: '.$hashCallable);
        }
        
        return sprintf('%s%s%d%s%s',
            $this->generateFileHash($file, $hashCallable),
            $this->getKeyDelimiter(),
            $config->getStorageDepth(),
            $this->getKeyDelimiter(),
            $config->getStorageName()
        );
    }
    
    /**
     * Get the string (char) used to separate key portions.
     *
     * @return string
     */
    public function getKeyDelimiter()
    {
        return $this->keyDelimiter;
    }
    
    /**
     * This generates the actual file hash by calling the hash callable, passing the file path.
     *
     * @param File $file
     * @param callable $hashCallable
     *
     * @return string
     */
    protected function generateFileHash(File $file, $hashCallable)
    {
        return call_user_func($hashCallable, $file->getPathname());
    }
    
    /**
     * Get the (local) file path for the given file key.
     *
     * @param string $fileKey
     *
     * @return $string
     */
    public function getFilePath($fileKey)
    {
        
    }
    
    /**
     * Get the URL for the given file key.
     *
     * @param string $fileKey
     *
     * @return $string
     */
    public function getFileUrl($fileKey)
    {
        
    }
    
    /**
     * Remove the given file from the storage.
     *
     * @param string $fileKey
     */
    public function removeFile($fileKey)
    {
        
    }
    
    /**
     * Check if the given file exists inside its storage.
     *
     * @param string $fileKey
     *
     * @return boolean
     */
    public function fileExists($fileKey)
    {
        
    }
    
    /**
     * Get the attachment meta data object for the given key.
     *
     * @param string $fileKey
     *
     * @return Attachment
     */
    public function getAttachment($fileKey)
    {
        return AttachmentQuery::create()
            ->filterByFileKey($fileKey)
            ->findOne()
        ;
    }
    
    /**
     * Check if the given Attachment object exists in the database.
     *
     * @param string $fileKey
     *
     * @return boolean
     */
    public function attachmentExists($fileKey)
    {
        return AttachmentQuery::create()
            ->filterByFileKey($fileKey)
            ->count() > 0
        ;
    }
}
