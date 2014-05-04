<?php

namespace c33s\AttachmentBundle\Attachment;

use c33s\AttachmentBundle\Model\Attachment;
use Symfony\Component\HttpFoundation\File\File;
use Knp\Bundle\GaufretteBundle\FilesystemMap;
use c33s\AttachmentBundle\Model\AttachmentQuery;
use c33s\AttachmentBundle\Model\AttachmentLink;
use Gaufrette\Adapter\Local;

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
    
    /**
     *
     * @var FilesystemMap
     */
    protected $filesystemMap;
    
    public function __construct(array $rawConfig, FilesystemMap $filesystemMap)
    {
        $this->rawConfig = $rawConfig;
        $this->filesystemMap = $filesystemMap;
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
            $this->configs[$configKey] = new AttachmentConfig($this->rawConfig['attachments'], $className, $fieldName);
        }
        
        return $this->configs[$configKey];
    }
    
    /**
     * Move the file to the storage as defined in the given file key.
     *
     * @param File $file
     * @param string $fileKey
     */
    protected function moveToStorage(File $file, $fileKey)
    {
        $config = $this->getStorageConfigForFileKey($fileKey);
        
        $filesystem = $config->getFilesystem();
        $storagePath = $config->getStoragePath();
        
        if (!$filesystem->has($storagePath))
        {
            $size = $file->getSize();
            $writtenSize = $filesystem->write($storagePath, file_get_contents($file->getPathname()));
            
            if ($writtenSize != $size)
            {
                throw new \RuntimeException('Error writing file to storage');
            }
        }
        
        unlink($file->getPathname());
    }
    
    /**
     * Extract info from the given key.
     *
     * @param string $key
     *
     * @return StorageConfig
     */
    protected function getStorageConfigForFileKey($fileKey)
    {
        if (!isset($this->configs[$fileKey]))
        {
            $config = new StorageConfig($fileKey, $this->rawConfig['storages'], $this->getKeyDelimiter());
            
            $config->setFilesystem($this->filesystemMap->get($config->getFilesystemName()));
            
            $this->configs[$fileKey] = $config;
        }
        
        return $this->configs[$fileKey];
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
        
        $extension = $file->getExtension();
        if ('' != $extension)
        {
            $extension = '.'.str_replace($this->getKeyDelimiter(), '', $extension);
        }
        
        return sprintf('%s%s%s%d%s%s',
            $this->generateFileHash($file, $hashCallable),
            $extension,
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
     * Get the (local) file for the given file key.
     *
     * @todo    Gaufrette\Adapter\Local does not expose its directory, find another way.
     *
     * @param string $fileKey
     *
     * @return Gaufrette\File
     */
    public function getFilePath($fileKey)
    {
        
    }
    
    /**
     * Check if the given file is stored locally.
     *
     * @param string $fileKey
     *
     * @return boolean
     */
    public function isLocalFile($fileKey)
    {
        $config = $this->getStorageConfigForFileKey($fileKey);
        
        return $config->getFilesystem()->getAdapter() instanceof Local;
    }
    
    /**
     * Check if there could be a URL to this key.
     *
     * @param string $fileKey
     *
     * @return boolean
     */
    public function hasFileUrl($fileKey)
    {
        $this->getStorageConfigForFileKey($fileKey)->hasBaseUrl();
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
        return $this->getStorageConfigForFileKey($fileKey)->getFileUrl();
    }
    
    /**
     * Remove the given file from the storage.
     *
     * @param string $fileKey
     */
    protected function removeFile($fileKey)
    {
        $config = $this->getStorageConfigForFileKey($fileKey);
        
        $config->getFilesystem()->delete($config->getStoragePath());
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
        $config = $this->getStorageConfigForFileKey($fileKey);
        
        return $config->getFilesystem()->has($config->getStoragePath());
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
