<?php

namespace c33s\AttachmentBundle\Attachment;

use Symfony\Component\HttpFoundation\File\File;
use c33s\AttachmentBundle\Model\Attachment;

interface AttachmentHandlerInterface
{
    /**
     * Store a new attachment file for the given object and optional object field name.
     * There is no need for the field name to exist explicitly inside the object.
     *
     * @param File $file
     * @param AttachableObjectInterface $object
     * @param string $fieldName
     * @param boolean $deleteAfterCopy  Override default file deletion behavior
     *
     * @return Attachment   The created Attachment object
     */
    public function storeAndAttachFile(File $file, AttachableObjectInterface $object, $fieldName = null, $deleteAfterCopy = null);
    
    /**
     * Attach directory structure to an object. If no fieldName is provided, files inside the directory will be attached
     * as "general" files (no fieldname), files in direct sub directories will be added using the directory name as fieldName.
     *
     * Files and folders starting with a "." will be ignored.
     *
     * @param File $file
     * @param AttachableObjectInterface $object
     * @param string $fieldName
     * @param boolean $deleteAfterCopy  Override default file deletion behavior
     *
     * @return int      Number of files that have been attached in total
     */
    public function storeAndAttachDirectory($directory, AttachableObjectInterface $object, $fieldName = null, $deleteAfterCopy = null);
    
    /**
     * Check if the given file is stored locally.
     * This checks for the "Local" Gaufrette Adapter.
     *
     * @param string $key
     *
     * @return boolean
     */
    public function hasLocalFile($key);
    
    /**
     * Check if there could be a URL to this key.
     *
     * @param string $key
     *
     * @return boolean
     */
    public function hasFileUrl($key);
    
    /**
     * Get the URL for the given file key.
     *
     * @param string $key
     *
     * @return $string
     */
    public function getFileUrl($key);
    
    /**
     * Get a File object for the given key. The file has to exist locally (the storage needs a base path)
     *
     * @param string $key
     *
     * @return File
     */
    public function getFile($key);
    
    /**
     * Check if the given file exists inside its storage. Actually you should never have to do this unless you are
     * manipulating the storage by hand.
     *
     * @param string $key
     *
     * @return boolean
     */
    public function fileExists($key);
    
    /**
     * Get the attachment meta data object for the given key.
     *
     * @param string $key
     *
     * @return Attachment
     */
    public function getAttachment($key);
    
    /**
     * Check if the given Attachment object exists in the database.
     *
     * @param string $key
     *
     * @return boolean
     */
    public function attachmentExists($key);
}
