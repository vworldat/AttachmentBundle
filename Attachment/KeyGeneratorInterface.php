<?php

namespace c33s\AttachmentBundle\Attachment;

interface keyGeneratorInterface
{
    /**
     * Convert the given array of fields into a file key.
     *
     * @param array $fields
     *
     * @return string
     */
    public function fieldsToKey(array $fields);
    
    /**
     * Extract fields from file key.
     *
     * @param string $key
     *
     * @return array
     */
    public function keyToFields($key);
}
