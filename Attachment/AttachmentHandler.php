<?php

namespace c33s\AttachmentBundle\Attachment;

use c33s\AttachmentBundle\Exception\CouldNotWriteToStorageException;
use c33s\AttachmentBundle\Exception\FilesystemDoesNotExistException;
use c33s\AttachmentBundle\Exception\InputFileNotReadableException;
use c33s\AttachmentBundle\Exception\InputFileNotWritableException;
use c33s\AttachmentBundle\Exception\InvalidAttachableFieldNameException;
use c33s\AttachmentBundle\Exception\InvalidHashCallableException;
use c33s\AttachmentBundle\Exception\StorageDoesNotExistException;
use c33s\AttachmentBundle\Model\Attachment;
use c33s\AttachmentBundle\Model\AttachmentLink;
use c33s\AttachmentBundle\Model\AttachmentLinkQuery;
use c33s\AttachmentBundle\Model\AttachmentQuery;
use c33s\AttachmentBundle\Schema\AttachmentSchema;
use c33s\AttachmentBundle\Storage\Storage;
use Knp\Bundle\GaufretteBundle\FilesystemMap;
use Symfony\Component\Finder\Finder;
use Symfony\Component\HttpFoundation\File\File;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use c33s\AttachmentBundle\Exception\MissingStorageConfigException;
use c33s\AttachmentBundle\Exception\AttachmentException;

/**
 * AttachmentHandler is the service gapping the bridge between actual files (residing in Gaufrette storages)
 * and meta data (in Attachment database objects).
 *
 * @author david
 *
 */
class AttachmentHandler implements AttachmentHandlerInterface
{
    /**
     *
     * @var array[Storage]
     */
    protected $storages = array();
    
    /**
     *
     * @var array[AttachmentSchema]
     */
    protected $attachmentSchemas = array();
    
    /**
     *
     * @var AttachmentSchema
     */
    protected $defaultAttachmentSchema;
    
    /**
     *
     * @var FilesystemMap
     */
    protected $filesystemMap;
    
    /**
     * Key class to use for generating and parsing attachment file keys.
     * Override to implement custom key pattern logic.
     *
     * @var string
     */
    protected $fileKeyClass = 'c33s\\AttachmentBundle\\Attachment\\FileKey';
    
    /**
     * Remembered key => FileKey associations
     *
     * @var array
     */
    protected $fileKeys = array();
    
    /**
     *
     * @param array $storageConfig          The "storages" path of the config
     * @param array $attachmentConfig       The "attachments" path of the config
     * @param FilesystemMap $filesystemMap  The FilesystemMap provided by KnpGaufretteBundle
     *
     * @throws FilesystemDoesNotExistException
     * @throws StorageDoesNotExistException
     * @throws InvalidHashCallableException
     */
    public function __construct(array $storageConfig, array $attachmentConfig, FilesystemMap $filesystemMap)
    {
        $this->filesystemMap = $filesystemMap;
        
        foreach ($storageConfig as $storageName => $config)
        {
            try
            {
                $filesystem = $filesystemMap->get($config['filesystem']);
            }
            catch (\InvalidArgumentException $e)
            {
                throw new FilesystemDoesNotExistException('Unknown gaufrette filesystem name: '.$config['filesystem'].' used for storage '.$storageName);
            }
            
            $this->storages[$storageName] = new Storage($storageName, $filesystem, $config['path_prefix'], $config['base_url'], $config['base_path']);
        }
        
        $this->defaultAttachmentSchema = $this->createAttachmentSchema($attachmentConfig);
        
        foreach ($attachmentConfig['models'] as $modelName => $modelConfig)
        {
            $this->attachmentSchemas[$modelName] = $this->createAttachmentSchema($modelConfig);
            
            foreach ($modelConfig['fields'] as $fieldName => $fieldConfig)
            {
                $this->attachmentSchemas[$modelName][$fieldName] = $this->createAttachmentSchema($fieldConfig);
            }
        }
    }
    
    /**
     * Create AttachmentSchema object holding information about how to handle specific model classes and field names.
     *
     * @param array $config
     * @throws StorageDoesNotExistException
     *
     * @return AttachmentSchema
     */
    protected function createAttachmentSchema(array $config)
    {
        $storage = $this->getStorage($config['storage']);
        
        return new AttachmentSchema($storage, $config['hash_callable'], $config['storage_depth']);
    }
    
    /**
     * Store a new attachment file for the given object and optional object field name.
     * There is no need for the field name to exist explicitly inside the object.
     *
     * @throws InputFileNotReadableException
     * @throws InputFileNotWritableException
     * @throws CouldNotWriteToStorageException
     *
     * @param File $file
     * @param AttachableObjectInterface $object
     * @param string $fieldName
     * @param boolean $deleteAfterCopy  Override default file deletion behavior
     *
     * @return Attachment   The created Attachment object
     */
    public function storeAndAttachFile(File $file, AttachableObjectInterface $object, $fieldName = null, $deleteAfterCopy = null)
    {
        $this->checkAttachableObject($object);
        
        if (null === $deleteAfterCopy)
        {
            $deleteAfterCopy = $this->guessDeleteAfterCopy($file, $object, $fieldName);
        }
        
        $this->checkFile($file, $deleteAfterCopy);
        
        $fileKey = $this->generateKey($file, $object, $fieldName);
        $this->copyToStorage($file, $fileKey);
        $attachment = $this->saveToDatabase($file, $object, $fieldName, $fileKey);
        
        if ($deleteAfterCopy)
        {
            unlink($file->getRealPath());
        }
        
        return $attachment;
    }
    
    /**
     * Attach directory structure to an object. If no fieldName is provided, files inside the directory will be attached
     * as "general" files (no fieldname), files in direct sub directories will be added using the directory name as fieldName.
     *
     * Files and folders starting with a "." will be ignored.
     *
     * @throws InputFileNotReadableException
     * @throws InputFileNotWritableException
     * @throws CouldNotWriteToStorageException
     *
     * @param File $file
     * @param AttachableObjectInterface $object
     * @param string $fieldName
     * @param boolean $deleteAfterCopy  Override default file deletion behavior
     *
     * @return int      Number of files that have been attached in total
     */
    public function storeAndAttachDirectory($directory, AttachableObjectInterface $object, $fieldName = null, $deleteAfterCopy = null)
    {
        $finder = Finder::create()
            ->ignoreDotFiles(true)
            ->files()
            ->sortByType()
            ->depth(0)
            ->in($directory)
        ;
        
        $count = 0;
        
        foreach ($finder as $file)
        {
            $this->storeAndAttachFile(new File($file->getRealPath()), $object, $fieldName, $deleteAfterCopy);
            ++$count;
        }
        
        if (null === $fieldName)
        {
            $finder = Finder::create()
                ->ignoreDotFiles(true)
                ->directories()
                ->sortByType()
                ->depth(0)
                ->in($directory)
            ;
            
            foreach ($finder as $dir)
            {
                $fieldName = $dir->getFileName();
                
                $count += $this->storeAndAttachDirectory($dir->getRealPath(), $object, $fieldName, $deleteAfterCopy);
            }
        }
        
        return $count;
    }
    
    /**
     * Check if the AttachableObject actually delivers and ID that is not null
     *
     * @param AttachableObjectInterface $object
     * @throws AttachmentException
     */
    protected function checkAttachableObject(AttachableObjectInterface $object)
    {
        if (null === $object->getAttachableId())
        {
            throw new AttachmentException('The object did not provide a valid ID to store the attachment link');
        }
    }
    
    /**
     * Guess the "delete after copy" setting if NULL was given.
     * The default logic is to delete input files if they were uploaded, so it checks the file class for UploadedFile
     *
     * @param File $file
     * @param AttachableObjectInterface $object
     * @param string $fieldName
     *
     * @return boolean
     */
    protected function guessDeleteAfterCopy(File $file, AttachableObjectInterface $object, $fieldName = null)
    {
        return $file instanceof UploadedFile;
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
     * Fetch the schema to use for the given object type and fieldname. This checks if there are specific settings
     * for the given object/fieldName combination and else returns the default schema.
     *
     * @param AttachableObjectInterface $object
     * @param string $fieldName
     *
     * @return AttachmentSchema
     */
    protected function getSchemaForObject(AttachableObjectInterface $object, $fieldName)
    {
        $className = $object->getAttachableClassName();
        
        if (isset($this->attachmentSchemas[$className][$fieldName]))
        {
            return $this->attachmentSchemas[$className][$fieldName];
        }
        if (isset($this->attachmentSchemas[$className]))
        {
            return $this->attachmentSchemas[$className];
        }
        
        return $this->defaultAttachmentSchema;
    }
    
    /**
     * Generate the file key for the given file path and config.
     *
     * @param File $file
     * @param AttachmentSchema $schema
     *
     * @return FileKey
     */
    protected function generateKey(File $file, AttachableObjectInterface $object, $fieldName)
    {
        $schema = $this->getSchemaForObject($object, $fieldName);
        
        if ($file instanceof UploadedFile)
        {
            $extension = $file->getClientOriginalExtension();
        }
        else
        {
            $extension = $file->getExtension();
        }
        
        $hash = $this->generateFileHash($file, $schema);
        
        $fileKey = new $this->fileKeyClass();
        $fileKey
            ->setHash($hash)
            ->setExtension($extension)
            ->setDepth($schema->getStorageDepth())
            ->setClassName($object->getAttachableClassName())
            ->setFieldName($fieldName)
            ->setStorageName($schema->getStorage()->getName())
        ;
        
        // This triggers the generation inside the key. If there are any exceptions, we want them here for clarity.
        $fileKey->getKey();
        
        return $fileKey;
    }
    
    /**
     * This generates the actual file hash by calling the hash callable, passing the file path.
     *
     * @param File $file
     * @param AttachmentSchema $schema
     *
     * @return string
     */
    protected function generateFileHash(File $file, AttachmentSchema $schema)
    {
        return call_user_func($schema->getHashCallable(), $file->getRealPath());
    }
    
    /**
     * Get the storage with the given name
     *
     * @param string $name
     *
     * @return Storage
     */
    protected function getStorage($name)
    {
        if (!array_key_exists($name, $this->storages))
        {
            throw new StorageDoesNotExistException('Invalid storage name: '.$name);
        }
        
        return $this->storages[$name];
    }
    
    /**
     * Move the file to the storage as defined in the given file key.
     *
     * @param File $file
     * @param string $fileKey
     */
    protected function copyToStorage(File $file, FileKey $fileKey)
    {
        $storage = $this->getStorage($fileKey->getStorageName());
        $storagePath = $this->getStoragePath($storage, $fileKey);
        
        $filesystem = $storage->getFilesystem();
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
     * Get the path inside the storage for the given Storage and FileKey.
     *
     * @param Storage $storage
     * @param FileKey $fileKey
     *
     * @return string
     */
    protected function getStoragePath(Storage $storage, FileKey $fileKey)
    {
        return ltrim($storage->getPathPrefix().'/'.$fileKey->getFilePath(), '/');
    }
    
    /**
     * Perform filling and saving of attachment database object
     *
     * @throws InvalidAttachableFieldNameException
     *
     * @param File $file
     * @param AttachableObjectInterface $object
     * @param string $fieldName
     * @param FileKey $fileKey
     *
     * @return Attachment
     */
    protected function saveToDatabase(File $file, AttachableObjectInterface $object, $fieldName, FileKey $fileKey)
    {
        $attachment = $this->getOrCreateAttachment($file, $object, $fieldName, $fileKey);
        $link = $this->createAttachmentLink($file, $object, $fieldName, $attachment);
        
        if (in_array($fieldName, $object->getAttachableFieldNames()))
        {
            $method = 'set'.$fieldName.'Attachment';
        
            if (!method_exists($object, $method))
            {
                throw new InvalidAttachableFieldNameException('Fieldname setter for '.$fieldName.' does not exist in '.get_class($object));
            }
            
            $object->$method($attachment);
            $link->setIsCurrent(true);
            
            AttachmentLinkQuery::create()
                ->filterByAttachableObject($object)
                ->filterByModelField($fieldName)
                ->doUpdate(array('IsCurrent' => false), \Propel::getConnection())
            ;
        }
        
        $link->save();
        
        return $attachment;
    }
    
    /**
     * Get a new Attachment object or an existing one if a file with the same key was already stored.
     *
     * @param File $file
     * @param AttachableObjectInterface $object
     * @param string $fieldName
     * @param FileKey $fileKey
     *
     * @return Attachment
     */
    protected function getOrCreateAttachment(File $file, AttachableObjectInterface $object, $fieldName, FileKey $fileKey)
    {
        $attachment = $this->getAttachment($fileKey->getKey());
        
        if (null === $attachment)
        {
            $schema = $this->getSchemaForObject($object, $fieldName);
            
            $attachment = new Attachment();
            $attachment
                ->setFileKey($fileKey->getKey())
                ->setFileSize($file->getSize())
                ->setFileType($file->getMimeType())
                ->setStorageName($schema->getStorage()->getName())
                ->setStorageDepth($schema->getStorageDepth())
                ->setHashType($schema->getHashCallableAsString())
            ;
        }
        
        return $attachment;
    }
    
    /**
     * Create new AttachmentLink object for the given parameters.
     *
     * @param File $file
     * @param AttachableObjectInterface $object
     * @param string $fieldName
     * @param Attachment $attachment
     *
     * @return AttachmentLink
     */
    protected function createAttachmentLink(File $file, AttachableObjectInterface $object, $fieldName, Attachment $attachment)
    {
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
        
        return $link;
    }
    
    /**
     * Convert an existing string key to a FileKey object for further usage.
     *
     * @throws InvalidKeyException              if the key cannot be parsed
     *
     * @param string $key
     *
     * @return FileKey
     */
    protected function getFileKey($key)
    {
        $key = (string) $key;
        if (!array_key_exists($key, $this->fileKeys))
        {
            $this->fileKeys[$key] = new FileKey($key);
        }
        
        return $this->fileKeys[$key];
    }
    
    /**
     * Shortcut function for all those calls with string keys.
     *
     * @throws InvalidKeyException              if the key cannot be parsed
     * @throws StorageDoesNotExistException     if the storage defined by the key is unknown
     *
     * @param string $key
     *
     * @return StorageConfig
     */
    protected function getStorageByKey($key)
    {
        return $this->getStorage($this->getFileKey($key)->getStorageName());
    }
    
    /**
     * Remove the given file from the storage.
     *
     * @param string $key
     */
    protected function removeFile($key)
    {
        $fileKey = $this->getFileKey($key);
        $storage = $this->getStorage($fileKey->getStorageName());
        
        return $storage->getFilesystem()->delete($this->getStoragePath($storage, $fileKey));
    }
    
    /**
     * Check if the given file is stored locally.
     * This checks for the "Local" Gaufrette Adapter.
     *
     * @throws InvalidKeyException              if the key cannot be parsed
     * @throws StorageDoesNotExistException     if the storage defined by the key is unknown
     *
     * @param string $key
     *
     * @return boolean
     */
    public function hasLocalFile($key)
    {
        $storage = $this->getStorageByKey($key);
        
        return $storage->isLocal();
    }
    
    /**
     * Check if there could be a URL to this key.
     *
     * @throws InvalidKeyException              if the key cannot be parsed
     * @throws StorageDoesNotExistException     if the storage defined by the key is unknown
     *
     * @param string $key
     *
     * @return boolean
     */
    public function hasFileUrl($key)
    {
        return $this->getStorageByKey($key)->hasBaseUrl();
    }
    
    /**
     * Get the URL for the given file key.
     *
     * @throws InvalidKeyException              if the key cannot be parsed
     * @throws StorageDoesNotExistException     if the storage defined by the key is unknown
     * @throws MissingStorageConfigException    if the storage config for the key's storage does not contain a base url
     *
     * @param string $key
     *
     * @return $string
     */
    public function getFileUrl($key)
    {
        $fileKey = $this->getFileKey($key);
        $storage = $this->getStorage($fileKey->getStorageName());
        
        if (!$storage->hasBaseUrl())
        {
            throw new MissingStorageConfigException('This storage does not have a configured base url!');
        }
        
        return $storage->getBaseUrl().'/'.$this->getStoragePath($storage, $fileKey);
    }
    
    /**
     * Get a File object for the given key. The file has to exist locally (the storage needs a base path)
     *
     * @throws InvalidKeyException              if the key cannot be parsed
     * @throws StorageDoesNotExistException     if the storage defined by the key is unknown
     * @throws MissingStorageConfigException    if the storage config for the key's storage does not contain a base path
     *
     * @param string $key
     *
     * @return File
     */
    public function getFile($key)
    {
        $fileKey = $this->getFileKey($key);
        $storage = $this->getStorage($fileKey->getStorageName());
        
        if (!$storage->hasBasePath())
        {
            throw new MissingStorageConfigException('This storage does not have a configured base path!');
        }
        
        return new File($storage->getBasePath().'/'.$this->getStoragePath($storage, $fileKey));
    }
    
    /**
     * Check if the given file exists inside its storage. Actually you should never have to do this unless you are
     * manipulating the storage by hand.
     *
     * @throws InvalidKeyException              if the key cannot be parsed
     * @throws StorageDoesNotExistException     if the storage defined by the key is unknown
     *
     * @param string $key
     *
     * @return boolean
     */
    public function fileExists($key)
    {
        $fileKey = $this->getFileKey($key);
        $storage = $this->getStorage($fileKey->getStorageName());
        
        return $storage->getFilesystem()->has($this->getStoragePath($storage, $fileKey));
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
}
