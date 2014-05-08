<?php

namespace c33s\AttachmentBundle\Attachment;

use c33s\AttachmentBundle\Model\Attachment;
use Symfony\Component\HttpFoundation\File\File;
use Knp\Bundle\GaufretteBundle\FilesystemMap;
use c33s\AttachmentBundle\Model\AttachmentQuery;
use c33s\AttachmentBundle\Model\AttachmentLink;
use Gaufrette\Adapter\Local;
use c33s\AttachmentBundle\Exception\InputFileNotReadableException;
use c33s\AttachmentBundle\Exception\InputFileNotWritableException;
use c33s\AttachmentBundle\Exception\CouldNotWriteToStorageException;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use c33s\AttachmentBundle\Model\AttachmentLinkQuery;
use c33s\AttachmentBundle\Exception\StorageDoesNotExistException;

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
     * @param boolean $deleteAfterCopy  Set to false if you do not want the source file to be removed after copying it to the storage
     *
     * @return Attachment   The created Attachment object
     */
    public function storeAndAttachFile(File $file, AttachableObjectInterface $object, $fieldName = null, $deleteAfterCopy = true)
    {
        $this->checkFile($file, $deleteAfterCopy);
        
        $config = $this->getConfigForObject($object, $fieldName);
        $fileKey = $this->generateKey($file, $config);
        
        $this->copyToStorage($file, $fileKey, $deleteAfterCopy);
        
        $attachment = $this->getOrCreateAttachment($file, $config, $fileKey->getKey());
        
        if ($file instanceof UploadedFile)
        {
            $extension = $file->getClientOriginalExtension();
            $filename = $file->getClientOriginalName();
            $basename = basename($filename, '.'.$extension);
        }
        else
        {
            $extension = $file->getExtension();
            $filename = $file->getFilename();
            $basename = $file->getBasename('.'.$extension);
        }
        
        $link = new AttachmentLink();
        $link
           ->setAttachment($attachment)
           ->setModelName($object->getAttachableClassName())
           ->setModelId($object->getAttachableId())
           ->setModelField($fieldName)
           ->setFileName($filename)
           ->setFileExtension($extension)
           ->setCustomName($basename)
        ;
        
        if (in_array($fieldName, $object->getAttachableFieldNames()))
        {
            $method = 'set'.$fieldName.'Attachment';
            
            if (!method_exists($object, $method))
            {
                throw new \RuntimeException('Fieldname setter for '.$fieldName.' does not exist in '.get_class($object));
            }
            
            $object->$method($attachment);
            $link->setIsCurrent(true);
            
            AttachmentLinkQuery::create()
                ->filterByAttachableObject($object)
                ->filterByModelField($fieldName)
                ->doUpdate(array('IsCurrent' => false), \Propel::getConnection())
            ;
        }
        
        /**
         * TODO: this will not work if the related object is new and does not have an id yet.
         */
        $link->save();
        
        if ($deleteAfterCopy)
        {
            unlink($file->getRealPath());
        }
        
        return $attachment;
    }
    
    /**
     * Perform some checks to see if the given file is valid and ready to use.
     *
     * @throws InputFileNotReadableException
     * @throws InputFileNotWritableException
     *
     * @param File $file
     * @param boolean $deleteAfterCopy
     *
     * @return boolean
     */
    protected function checkFile(File $file, $deleteAfterCopy)
    {
        if (!$file->isReadable())
        {
            throw new InputFileNotReadableException('File ' . $file->getRealPath() . ' is not readable');
        }
        
        if ($deleteAfterCopy && !$file->isWritable())
        {
            throw new InputFileNotWritableException('File ' . $file->getRealPath() . ' is not writable');
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
    protected function copyToStorage(File $file, FileKey $fileKey)
    {
        $config = $this->getStorageConfigForFileKey($fileKey);
        
        $filesystem = $config->getFilesystem();
        $storagePath = $config->getStoragePath();
        
        if (!$filesystem->has($storagePath))
        {
            $size = $file->getSize();
            $writtenSize = $filesystem->write($storagePath, file_get_contents($file->getRealPath()));
            
            if ($writtenSize != $size)
            {
                throw new CouldNotWriteToStorageException('Error copying file ' . $file->getRealPath() . ' to storage');
            }
        }
    }
    
    /**
     * Extract info from the given key.
     *
     * @param string $key
     *
     * @return StorageConfig
     */
    protected function getStorageConfigForFileKey(FileKey $fileKey)
    {
        if (!isset($this->configs[$fileKey->getKey()]))
        {
            $config = new StorageConfig($fileKey, $this->rawConfig['storages']);
            
            $config->setFilesystem($this->filesystemMap->get($config->getFilesystemName()));
            
            $this->configs[$fileKey->getKey()] = $config;
        }
        
        return $this->configs[$fileKey->getKey()];
    }
    
    /**
     * Shortcut function for all those calls with string keys.
     *
     * @param string $key
     *
     * @return StorageConfig
     */
    protected function getStorageConfigForKey($key)
    {
        return $this->getStorageConfigForFileKey($this->getFileKeyFromKey($key));
    }
    
    /**
     * Generate the file key for the given file path and config.
     *
     * @param File $file
     * @param AttachmentConfig $config
     *
     * @return FileKey
     */
    protected function generateKey(File $file, AttachmentConfig $config)
    {
        $hashCallable = $config->getHashCallable();
        
        if (!is_callable($hashCallable))
        {
            throw new \RuntimeException('Invalid file hashing callable: '.$hashCallable);
        }
        
        if ($file instanceof UploadedFile)
        {
            $extension = $file->getClientOriginalExtension();
        }
        else
        {
            $extension = $file->getExtension();
        }
        
        $fileKey = new FileKey();
        $fileKey
            ->setHash($this->generateFileHash($file, $hashCallable))
            ->setExtension($extension)
            ->setDepth($config->getStorageDepth())
            ->setClassName($config->getClassName())
            ->setFieldName($config->getFieldName())
            ->setStorageName($config->getStorageName())
        ;
        
        // This triggers the generation inside the key. If there are any exceptions, we want them here for clarity.
        $fileKey->getKey();
        
        return $fileKey;
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
        return call_user_func($hashCallable, $file->getRealPath());
    }
    
    /**
     * Check if the given file is stored locally.
     *
     * @param string $key
     *
     * @return boolean
     */
    public function isLocalFile($key)
    {
        $config = $this->getStorageConfigForKey($key);
        
        return $config->getFilesystem()->getAdapter() instanceof Local;
    }
    
    /**
     * Check if there could be a URL to this key.
     *
     * @param string $key
     *
     * @return boolean
     */
    public function hasFileUrl($key)
    {
        return $this->getStorageConfigForKey($key)->hasBaseUrl();
    }
    
    /**
     * Get the URL for the given file key.
     *
     * @param string $key
     *
     * @return $string
     */
    public function getFileUrl($key)
    {
        try
        {
            return $this->getStorageConfigForKey($key)->getFileUrl();
        }
        catch (MissingStorageConfigException $e)
        {
            return null;
        }
    }
    
    /**
     * Get a File object for the given key. The file has to exist locally.
     *
     * @param string $key
     *
     * @return File
     */
    public function getFile($key)
    {
        try
        {
            return $this->getStorageConfigForKey($key)->getFile();
        }
        catch (StorageDoesNotExistException $e)
        {
            return null;
        }
        catch (MissingStorageConfigException $e)
        {
            return null;
        }
    }
    
    /**
     * Remove the given file from the storage.
     *
     * @param string $key
     */
    protected function removeFile($key)
    {
        $config = $this->getStorageConfigForKey($key);
        
        return $config->getFilesystem()->delete($config->getStoragePath());
    }
    
    /**
     * Check if the given file exists inside its storage.
     *
     * @param string $key
     *
     * @return boolean
     */
    public function fileExists($key)
    {
        $config = $this->getStorageConfigForKey($key);
        
        return $config->getFilesystem()->has($config->getStoragePath());
    }
    
    /**
     * Get the attachment meta data object for the given key.
     *
     * @param string $key
     *
     * @return Attachment
     */
    public function getAttachment($key)
    {
        return AttachmentQuery::create()
            ->filterByFileKey($key)
            ->findOne()
        ;
    }
    
    /**
     * Check if the given Attachment object exists in the database.
     *
     * @param string $key
     *
     * @return boolean
     */
    public function attachmentExists($key)
    {
        return AttachmentQuery::create()
            ->filterByFileKey($key)
            ->count() > 0
        ;
    }
    
    /**
     * Get a new Attachment object or an existing one if a file with the same key was already stored.
     *
     * @param File $file
     * @param AttachmentConfig $config
     * @param string $key
     *
     * @return Attachment
     */
    protected function getOrCreateAttachment(File $file, AttachmentConfig $config, $key)
    {
        $attachment = $this->getAttachment($key);
        
        if (null === $attachment)
        {
            $attachment = new Attachment();
            $attachment
                ->setFileKey($key)
                ->setFileSize($file->getSize())
                ->setFileType($file->getMimeType())
                ->setStorageName($config->getStorageName())
                ->setStorageDepth($config->getStorageDepth())
                ->setHashType($config->getHashCallableAsString())
            ;
        }
        
        return $attachment;
    }
    
    /**
     * Convert an existing string key to a FileKey object for further usage.
     *
     * @param string $key
     * @return FileKey
     */
    public function getFileKeyFromKey($key)
    {
        return FileKey::fromKey($key);
    }
}
