<?php

namespace c33s\AttachmentBundle\File;

/**
 * Attachment handlers handle attachment files. Simple as that.
 *
 * @author david
 */
interface FileHandlerInterface
{
    /**
     * Check if the handler has a file with the given key.
     *
     * @param string $key
     *
     * @return boolean
     */
    public function hasFile($key);
    
    /**
     * Add a file to the storage.
     *
     * @param string $key
     * @param string $localPath
     *
     * @return string
     */
    public function addFile($key, $localPath);
    
    /**
     * Remove the given file from the storage.
     *
     * @param string $key
     */
    public function removeFile($key);
    
    /**
     * Get the file uri for the given key.
     *
     * @param string $key
     *
     * @return string
     */
    public function getFileUri($key);
}
